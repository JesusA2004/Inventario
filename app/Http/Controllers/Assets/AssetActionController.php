<?php

namespace App\Http\Controllers\Assets;

use App\Enums\AssetStatus;
use App\Enums\DecommissionReason;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AssetMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class AssetActionController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AssetMovementService $movements) {}

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
        ]);

        $original = $asset->getAttributes();
        $asset->update(['current_responsible_id' => $data['current_responsible_id'] ?? null]);
        $this->movements->logChanges($asset, $original);

        if (! empty($data['comment'])) {
            $this->movements->log($asset, MovementType::CambioResponsable, comment: $data['comment']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Responsable actualizado.']);

        return back();
    }

    public function changeLocation(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

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
            'reviewed_at' => ['required', 'date'],
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
            'date' => ['required', 'date'],
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
