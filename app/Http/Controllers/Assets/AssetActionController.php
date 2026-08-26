<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetStatus;
use App\Enums\DecommissionReason;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use App\Services\AssetMovementService;
use App\Services\Responsibilities\AssetResponsibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AssetActionController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AssetMovementService $movements,
        private readonly AssetResponsibilityService $responsibilities,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:editar-activos', only: ['changeResponsible', 'changeLocation', 'review']),
            new Middleware('permission:dar-de-baja-activos', only: ['decommission', 'reactivate']),
        ];
    }

    public function changeResponsible(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'current_responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'delivery_date' => ['nullable', 'date'],
            'generate_responsibility' => ['boolean'],
            'grantor_name' => ['nullable', 'string', 'max:255'],
            'grantor_position' => ['nullable', 'string', 'max:255'],
            'grantor_email' => ['nullable', 'email', 'max:255'],
        ]);

        // El frontend ya filtra la lista de responsables por empresa, pero
        // eso no impide un POST manipulado: el backend nunca debe confiar
        // solo en eso.
        if (! empty($data['current_responsible_id'])) {
            $responsible = ResponsiblePerson::find((int) $data['current_responsible_id']);

            if ($responsible && $responsible->company_id !== $asset->company_id) {
                throw ValidationException::withMessages([
                    'current_responsible_id' => 'El responsable seleccionado no pertenece a la empresa del activo.',
                ]);
            }
        }

        $original = $asset->getAttributes();
        $asset->update(['current_responsible_id' => $data['current_responsible_id'] ?? null]);
        $this->movements->logChanges($asset, $original);

        if (! empty($data['comment'])) {
            $this->movements->log($asset, MovementType::CambioResponsable, comment: $data['comment']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable actualizado.']);

        // "Generar responsiva de entrega" está activado por defecto cuando
        // se asigna un responsable real; el otorgante solo puede
        // sobreescribirse si el usuario tiene permiso para gestionar
        // responsivas, para no dejar que cualquiera con permiso de editar
        // activos cambie quién "otorga" el equipo.
        $shouldGenerate = ! empty($data['current_responsible_id']) && $request->boolean('generate_responsibility', true);

        if ($shouldGenerate) {
            $overrides = $request->user()->can('gestionar-responsivas')
                ? array_filter([
                    'grantor_name' => $data['grantor_name'] ?? null,
                    'grantor_position' => $data['grantor_position'] ?? null,
                    'grantor_email' => $data['grantor_email'] ?? null,
                ], fn ($value) => filled($value))
                : [];

            if (! empty($data['delivery_date'])) {
                $overrides['issued_at'] = $data['delivery_date'];
            }

            $responsible = ResponsiblePerson::findOrFail((int) $data['current_responsible_id']);
            $responsibility = $this->responsibilities->createDraft($asset->fresh(), $responsible, $overrides, $request->user());

            Inertia::flash('responsibilityCreated', [
                'public_id' => $responsibility->public_id,
                'receiver_name' => $responsibility->receiver_name,
                'has_email' => filled($responsibility->receiver_email),
            ]);
        }

        return back();
    }

    public function changeLocation(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $branch = Branch::find((int) $data['branch_id']);

        if ($branch && $branch->company_id !== $asset->company_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'La sucursal seleccionada no pertenece a la empresa del activo.',
            ]);
        }

        if (! empty($data['department_id'])) {
            $department = Department::find((int) $data['department_id']);

            if ($department && $department->company_id !== null && $department->company_id !== $asset->company_id) {
                throw ValidationException::withMessages([
                    'department_id' => 'El área seleccionada no pertenece a la empresa del activo.',
                ]);
            }
        }

        $original = $asset->getAttributes();
        $asset->update([
            'branch_id' => $data['branch_id'],
            'department_id' => $data['department_id'] ?? null,
        ]);
        $this->movements->logChanges($asset, $original);

        if (! empty($data['comment'])) {
            $this->movements->log($asset, MovementType::CambioSucursal, comment: $data['comment']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ubicación actualizada.']);

        return back();
    }

    public function review(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'reviewed_at' => [
                'required',
                'date',
                ...($asset->acquired_at ? ['after_or_equal:'.$asset->acquired_at->toDateString()] : []),
            ],
            'physical_status' => ['required', 'string', 'max:100'],
            'location_ok' => ['boolean'],
            'responsible_ok' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $asset->reviews()->create([
            'user_id' => $request->user()->id,
            'reviewed_at' => $data['reviewed_at'],
            'physical_status' => $data['physical_status'],
            'location_ok' => $data['location_ok'] ?? true,
            'responsible_ok' => $data['responsible_ok'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        $asset->update(['last_reviewed_at' => $data['reviewed_at']]);

        $this->movements->log(
            $asset,
            MovementType::Revision,
            comment: "Estado físico: {$data['physical_status']}".(($data['notes'] ?? null) ? ' — '.$data['notes'] : ''),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Revisión registrada correctamente.']);

        return back();
    }

    public function decommission(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'date' => [
                'required',
                'date',
                ...($asset->acquired_at ? ['after_or_equal:'.$asset->acquired_at->toDateString()] : []),
            ],
            'reason' => ['required', new Enum(DecommissionReason::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reason = DecommissionReason::from($data['reason']);

        $asset->update([
            'in_inventory' => false,
            'status' => AssetStatus::Baja,
            'decommissioned_at' => $data['date'],
            'decommission_reason' => $reason->value,
            'decommission_notes' => $data['notes'] ?? null,
        ]);

        $this->movements->log(
            $asset,
            MovementType::Baja,
            comment: "Motivo: {$reason->label()}".(($data['notes'] ?? null) ? ' — '.$data['notes'] : ''),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Activo dado de baja.']);

        return back();
    }

    public function reactivate(Asset $asset): RedirectResponse
    {
        $asset->update([
            'in_inventory' => true,
            'status' => AssetStatus::Activo,
            'decommissioned_at' => null,
            'decommission_reason' => null,
            'decommission_notes' => null,
        ]);

        $this->movements->log($asset, MovementType::Reactivacion, comment: 'El activo fue reactivado en el inventario.');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Activo reactivado.']);

        return back();
    }
}
