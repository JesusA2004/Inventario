<?php

namespace App\Http\Controllers\Public;

use App\Enums\AssetResponsibilityStatus;
use App\Http\Controllers\Controller;
use App\Mail\ResponsibilitySignedMail;
use App\Models\AssetResponsibility;
use App\Services\Responsibilities\AssetResponsibilityPdfService;
use App\Services\Responsibilities\ResponsibilitySigningTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Página pública de firma. Nunca usa public_id como único control de
 * acceso: mientras la responsiva no esté en un estado final, siempre exige
 * un token de alta entropía válido (comparado por hash, con expiración).
 */
class PublicResponsibilitySigningController extends Controller
{
    public function __construct(
        private readonly ResponsibilitySigningTokenService $tokens,
        private readonly AssetResponsibilityPdfService $pdf,
    ) {}

    public function show(Request $request, AssetResponsibility $responsibility): Response
    {
        if ($responsibility->status === AssetResponsibilityStatus::FirmadaDigitalmente) {
            return Inertia::render('public/responsibilities/Sign', [
                'state' => 'signed',
                'responsibility' => $this->publicPayload($responsibility),
                'downloadUrl' => $this->signedDownloadUrl($responsibility),
            ]);
        }

        if ($responsibility->status->isFinal()) {
            return Inertia::render('public/responsibilities/Sign', ['state' => 'already_signed']);
        }

        $token = $request->query('token');

        if (! $this->tokens->verify($responsibility, $token)) {
            abort_unless($this->tokens->isExpired($responsibility), 404);

            return Inertia::render('public/responsibilities/Sign', ['state' => 'expired']);
        }

        if (! $responsibility->viewed_at) {
            $responsibility->forceFill(['viewed_at' => now()])->save();
        }

        if ($responsibility->status === AssetResponsibilityStatus::Enviada) {
            $responsibility->update(['status' => AssetResponsibilityStatus::Vista]);
        }

        return Inertia::render('public/responsibilities/Sign', [
            'state' => 'ok',
            'token' => $token,
            'responsibility' => $this->publicPayload($responsibility),
            'pdfPreviewUrl' => route('public.responsibilities.preview', ['responsibility' => $responsibility->public_id, 'token' => $token]),
            'privacyNoticeUrl' => route('privacy-notice.show'),
        ]);
    }

    public function previewPdf(Request $request, AssetResponsibility $responsibility): StreamedResponse
    {
        if (! $responsibility->status->isFinal()) {
            abort_unless($this->tokens->verify($responsibility, $request->query('token')), 404);
        }

        $path = $this->pdf->unsigned($responsibility);

        return Storage::disk('local')->response($path, 'responsiva.pdf', ['Content-Disposition' => 'inline; filename="responsiva.pdf"']);
    }

    public function sign(Request $request, AssetResponsibility $responsibility): RedirectResponse
    {
        abort_if($responsibility->status->isFinal(), 404);
        abort_unless($this->tokens->verify($responsibility, $request->input('token')), 404);

        $data = $request->validate([
            'consent_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
            'signature' => ['required', 'string'],
        ]);

        $bytes = $this->decodeSignaturePng($data['signature']);

        $signaturePath = "responsibilities/{$responsibility->id}/firma-{$responsibility->public_id}.png";
        Storage::disk('local')->put($signaturePath, $bytes);

        $responsibility->update([
            'signature_path' => $signaturePath,
            'signature_sha256' => hash('sha256', $bytes),
            'signer_ip' => $request->ip(),
            'signer_user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'signed_at' => now(),
            'consent_accepted_at' => now(),
            'privacy_accepted_at' => now(),
            'status' => AssetResponsibilityStatus::FirmadaDigitalmente,
        ]);

        $this->pdf->signed($responsibility->fresh());
        $this->tokens->invalidate($responsibility);

        Mail::to($responsibility->receiver_email)->queue(
            new ResponsibilitySignedMail($responsibility->fresh(), $this->signedDownloadUrl($responsibility)),
        );

        return redirect()->route('public.responsibilities.show', ['responsibility' => $responsibility->public_id]);
    }

    public function downloadSigned(AssetResponsibility $responsibility): StreamedResponse
    {
        abort_unless($responsibility->signed_pdf_path !== null, 404);

        return Storage::disk('local')->download($responsibility->signed_pdf_path, "responsiva-firmada-{$responsibility->document_number}.pdf");
    }

    /**
     * Decodifica el PNG dibujado en el canvas de firma. Un canvas vacío
     * exporta un PNG casi idéntico en cada navegador cuyo tamaño en bytes es
     * consistentemente muy pequeño; un trazo real siempre produce muchos
     * más bytes, así que ese umbral basta como validación de servidor
     * (la validación "de verdad" —exigir al menos un trazo— vive en el
     * canvas del frontend, que no permite continuar sin dibujar).
     */
    private function decodeSignaturePng(string $dataUrl): string
    {
        if (! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw ValidationException::withMessages(['signature' => 'La firma no es válida.']);
        }

        $bytes = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);

        if ($bytes === false || strlen($bytes) < 300) {
            throw ValidationException::withMessages(['signature' => 'Debes dibujar tu firma antes de continuar.']);
        }

        return $bytes;
    }

    private function signedDownloadUrl(AssetResponsibility $responsibility): string
    {
        return URL::temporarySignedRoute(
            'public.responsibilities.download-signed',
            now()->addHours(48),
            ['responsibility' => $responsibility->public_id],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function publicPayload(AssetResponsibility $responsibility): array
    {
        $snapshot = $responsibility->asset_snapshot ?? [];

        return [
            'document_number' => $responsibility->document_number,
            'document_title' => $snapshot['document_title'] ?? 'Responsiva de equipo',
            'asset_name' => $snapshot['asset']['name'] ?? null,
            'internal_code' => $snapshot['asset']['internal_code'] ?? null,
            'receiver_name' => $responsibility->receiver_name,
            'signed_at' => $responsibility->signed_at?->toDateString(),
        ];
    }
}
