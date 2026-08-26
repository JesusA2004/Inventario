<?php

namespace App\Http\Controllers\Responsibilities;

use App\Enums\AssetResponsibilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AssetResponsibilityResource;
use App\Mail\ResponsibilityInvitationMail;
use App\Models\Asset;
use App\Models\AssetResponsibility;
use App\Models\Company;
use App\Models\CompanyDocumentSetting;
use App\Models\ResponsiblePerson;
use App\Services\Responsibilities\AssetResponsibilityPdfService;
use App\Services\Responsibilities\AssetResponsibilityService;
use App\Services\Responsibilities\ResponsibilitySigningTokenService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetResponsibilityController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AssetResponsibilityService $service,
        private readonly AssetResponsibilityPdfService $pdf,
        private readonly ResponsibilitySigningTokenService $tokens,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-responsivas', only: ['index', 'show', 'preview', 'downloadUnsignedPdf', 'downloadSignedPdf', 'downloadPhysicalCopy']),
            new Middleware('permission:crear-responsivas', only: ['generateForAsset', 'updateCharacteristics']),
            new Middleware('permission:enviar-responsivas', only: ['send', 'resend']),
            new Middleware('permission:gestionar-responsivas', only: ['cancel', 'uploadPhysicalCopy']),
        ];
    }

    /**
     * Vista previa fiel del documento directamente en el navegador (misma
     * plantilla Blade que usa el PDF, mismos datos, sin pasar por dompdf):
     * se abre en una pestaña/iframe antes de descargar, enviar a firma o
     * imprimir, para confirmar que el formato es el correcto.
     */
    public function preview(AssetResponsibility $responsibility): View
    {
        return view('pdf.responsibilities.document', [
            ...$this->pdf->viewData($responsibility, forWeb: true),
            'totalPages' => null,
            'isWebPreview' => true,
        ]);
    }

    public function index(Request $request): Response
    {
        $responsibilities = $this->filteredQuery($request)
            ->with(['asset:id,public_id,internal_code,name', 'company:id,name', 'responsiblePerson:id,full_name,email'])
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AssetResponsibility $r) => (new AssetResponsibilityResource($r))->resolve());

        return Inertia::render('responsivas/Index', [
            'responsibilities' => $responsibilities,
            'filters' => $request->only(['q', 'company_id', 'responsible_person_id', 'asset_id', 'status', 'from', 'to', 'signed', 'physical']),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => AssetResponsibilityStatus::options(),
            'kpis' => $this->kpis($request),
        ]);
    }

    public function show(AssetResponsibility $responsibility): Response
    {
        $responsibility->load(['asset:id,public_id,internal_code,name', 'company:id,name', 'responsiblePerson:id,full_name,email', 'creator:id,name', 'physicalReceivedBy:id,name']);

        return Inertia::render('responsivas/Show', [
            'responsibility' => (new AssetResponsibilityResource($responsibility))->resolve(),
        ]);
    }

    /**
     * Genera manualmente una responsiva para el responsable actual del
     * activo: para uno que todavía no tiene documento (p. ej. activos dados
     * de alta antes de que existiera este módulo) o para volver a emitir
     * una adicional cuando ya existe una (con un motivo opcional, que queda
     * en el historial del activo).
     */
    public function generateForAsset(Request $request, Asset $asset): RedirectResponse
    {
        abort_if(! $asset->current_responsible_id, 422, 'El activo no tiene un responsable asignado.');

        $responsible = ResponsiblePerson::findOrFail($asset->current_responsible_id);

        $overrides = $request->validate([
            'grantor_name' => ['nullable', 'string', 'max:255'],
            'grantor_position' => ['nullable', 'string', 'max:255'],
            'grantor_email' => ['nullable', 'email', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $request->user()->can('gestionar-responsivas')) {
            unset($overrides['grantor_name'], $overrides['grantor_position'], $overrides['grantor_email']);
        }

        $responsibility = $this->service->createDraft($asset, $responsible, $overrides, $request->user());

        $this->flashCreated($responsibility);

        return back();
    }

    public function updateCharacteristics(Request $request, AssetResponsibility $responsibility): RedirectResponse
    {
        $data = $request->validate([
            'characteristics' => ['required', 'array', 'min:1', 'max:40'],
            'characteristics.*.name' => ['required', 'string', 'max:120'],
            'characteristics.*.value' => ['required', 'string', 'max:2000'],
        ]);

        $this->service->updateCharacteristics($responsibility, $data['characteristics']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Características actualizadas.']);

        return back();
    }

    public function send(AssetResponsibility $responsibility): RedirectResponse
    {
        if ($responsibility->status !== AssetResponsibilityStatus::Borrador) {
            throw ValidationException::withMessages(['status' => 'Esta responsiva ya no está en borrador.']);
        }

        if (($responsibility->asset_snapshot['legal_review_status'] ?? null) !== 'approved') {
            throw ValidationException::withMessages([
                'status' => 'La plantilla legal usada en esta responsiva no está aprobada todavía. Apruébala en Configuración → Documentos / Responsivas antes de enviarla a firma.',
            ]);
        }

        if (blank($responsibility->receiver_email)) {
            throw ValidationException::withMessages([
                'receiver_email' => 'Este responsable no tiene correo electrónico. Agrégalo antes de enviar para firma.',
            ]);
        }

        $settings = CompanyDocumentSetting::forCompany($responsibility->company);
        $token = $this->tokens->issue($responsibility, $settings->signing_link_valid_days);

        $responsibility->update(['status' => AssetResponsibilityStatus::Enviada, 'sent_at' => now()]);

        $this->sendInvitation($responsibility->fresh(), $token);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsiva enviada para firma electrónica.']);

        return back();
    }

    public function resend(AssetResponsibility $responsibility): RedirectResponse
    {
        if ($responsibility->status->isFinal()) {
            throw ValidationException::withMessages(['status' => 'Esta responsiva ya está en un estado final y no puede reenviarse.']);
        }

        if (blank($responsibility->receiver_email)) {
            throw ValidationException::withMessages([
                'receiver_email' => 'Este responsable no tiene correo electrónico. Agrégalo antes de reenviar.',
            ]);
        }

        // Reenviar siempre invalida el enlace anterior antes de emitir uno nuevo.
        $this->tokens->invalidate($responsibility);
        $settings = CompanyDocumentSetting::forCompany($responsibility->company);
        $token = $this->tokens->issue($responsibility, $settings->signing_link_valid_days);

        $responsibility->update([
            'status' => $responsibility->status === AssetResponsibilityStatus::Borrador
                ? AssetResponsibilityStatus::Enviada
                : $responsibility->status,
            'sent_at' => now(),
        ]);

        $this->sendInvitation($responsibility->fresh(), $token);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitación reenviada; el enlace anterior quedó invalidado.']);

        return back();
    }

    public function cancel(AssetResponsibility $responsibility): RedirectResponse
    {
        $this->service->cancel($responsibility);
        $this->tokens->invalidate($responsibility);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsiva cancelada.']);

        return back();
    }

    public function downloadUnsignedPdf(AssetResponsibility $responsibility): StreamedResponse
    {
        $path = $this->pdf->unsigned($responsibility);

        return Storage::disk('local')->download($path, "responsiva-{$responsibility->document_number}.pdf");
    }

    public function downloadSignedPdf(AssetResponsibility $responsibility): StreamedResponse
    {
        abort_unless($responsibility->signed_pdf_path !== null, 404);

        return Storage::disk('local')->download($responsibility->signed_pdf_path, "responsiva-firmada-{$responsibility->document_number}.pdf");
    }

    public function uploadPhysicalCopy(Request $request, AssetResponsibility $responsibility): RedirectResponse
    {
        if ($responsibility->status === AssetResponsibilityStatus::Cancelada) {
            throw ValidationException::withMessages(['status' => 'Esta responsiva está cancelada.']);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'received_at' => ['nullable', 'date'],
        ]);

        if ($responsibility->physical_copy_path) {
            Storage::disk('local')->delete($responsibility->physical_copy_path);
        }

        $path = $request->file('file')->store("responsibilities/{$responsibility->id}/fisica", 'local');

        $responsibility->update([
            'physical_copy_path' => $path,
            'physical_received_at' => $data['received_at'] ?? now(),
            'physical_received_by' => $request->user()->id,
            'status' => AssetResponsibilityStatus::FirmaFisicaCargada,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Copia física registrada correctamente.']);

        return back();
    }

    public function downloadPhysicalCopy(AssetResponsibility $responsibility): StreamedResponse
    {
        abort_unless($responsibility->physical_copy_path !== null, 404);

        return Storage::disk('local')->download($responsibility->physical_copy_path);
    }

    private function sendInvitation(AssetResponsibility $responsibility, string $token): void
    {
        $signingUrl = URL::to("/firmar/responsiva/{$responsibility->public_id}?token={$token}");

        Mail::to($responsibility->receiver_email)->queue(new ResponsibilityInvitationMail($responsibility, $signingUrl));
    }

    private function flashCreated(AssetResponsibility $responsibility): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsiva generada correctamente.']);
        Inertia::flash('responsibilityCreated', [
            'public_id' => $responsibility->public_id,
            'receiver_name' => $responsibility->receiver_name,
            'has_email' => filled($responsibility->receiver_email),
        ]);
    }

    /**
     * @return Builder<AssetResponsibility>
     */
    private function filteredQuery(Request $request): Builder
    {
        return AssetResponsibility::query()
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('receiver_name', 'like', "%{$search}%")
                        ->orWhere('receiver_email', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhereHas('asset', fn ($asset) => $asset
                            ->where('internal_code', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%"));
                });
            })
            ->when($request->integer('company_id'), fn ($query, $value) => $query->where('company_id', $value))
            ->when($request->integer('responsible_person_id'), fn ($query, $value) => $query->where('responsible_person_id', $value))
            ->when($request->integer('asset_id'), fn ($query, $value) => $query->where('asset_id', $value))
            ->when($request->string('status')->toString(), fn ($query, $value) => $query->where('status', $value))
            ->when($request->date('from'), fn ($query, $value) => $query->whereDate('issued_at', '>=', $value))
            ->when($request->date('to'), fn ($query, $value) => $query->whereDate('issued_at', '<=', $value))
            ->when($request->filled('signed'), fn ($query) => $request->boolean('signed')
                ? $query->whereNotNull('signed_at')
                : $query->whereNull('signed_at'))
            ->when($request->filled('physical'), fn ($query) => $request->boolean('physical')
                ? $query->whereNotNull('physical_copy_path')
                : $query->whereNull('physical_copy_path'));
    }

    /**
     * @return array<string, int>
     */
    private function kpis(Request $request): array
    {
        return [
            'pending_send' => (clone $this->filteredQuery($request))->where('status', AssetResponsibilityStatus::Borrador)->count(),
            'sent' => (clone $this->filteredQuery($request))->where('status', AssetResponsibilityStatus::Enviada)->count(),
            'viewed' => (clone $this->filteredQuery($request))->where('status', AssetResponsibilityStatus::Vista)->count(),
            'signed_digitally' => (clone $this->filteredQuery($request))->where('status', AssetResponsibilityStatus::FirmadaDigitalmente)->count(),
            'with_physical_copy' => (clone $this->filteredQuery($request))->whereNotNull('physical_copy_path')->count(),
            'expired' => (clone $this->filteredQuery($request))
                ->whereIn('status', [AssetResponsibilityStatus::Enviada, AssetResponsibilityStatus::Vista])
                ->where('signing_token_expires_at', '<', now())
                ->count(),
        ];
    }
}
