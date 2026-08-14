<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Department;
use App\Models\ResponsiblePerson;
use Illuminate\Support\Facades\Auth;

class AssetMovementService
{
    public function log(
        Asset $asset,
        MovementType $type,
        ?string $field = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $comment = null,
    ): void {
        $asset->movements()->create([
            'user_id' => Auth::id(),
            'type' => $type,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'comment' => $comment,
        ]);
    }

    /**
     * Compare an asset's attributes before/after an update and automatically
     * log one movement per meaningful field change (responsible, location,
     * status), plus a generic "edición" entry for anything else that moved.
     *
     * @param  array<string, mixed>  $original
     */
    public function logChanges(Asset $asset, array $original): void
    {
        $trackedAsMovements = [
            'current_responsible_id' => MovementType::CambioResponsable,
            'branch_id' => MovementType::CambioSucursal,
            'department_id' => MovementType::CambioArea,
            'status' => MovementType::CambioEstado,
        ];

        $labels = [
            'current_responsible_id' => fn ($value) => $value
                ? ResponsiblePerson::find($value)?->full_name
                : 'Sin asignar',
            'branch_id' => fn ($value) => $value ? Branch::find($value)?->name : null,
            'department_id' => fn ($value) => $value ? Department::find($value)?->name : 'Sin área',
            'status' => fn ($value) => $value instanceof AssetStatus ? $value->label() : (string) $value,
        ];

        $otherFieldsChanged = [];

        foreach ($asset->getAttributes() as $field => $newValue) {
            if (! array_key_exists($field, $original)) {
                continue;
            }

            $oldValue = $original[$field];

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            if (isset($trackedAsMovements[$field])) {
                $this->log(
                    $asset,
                    $trackedAsMovements[$field],
                    $field,
                    $labels[$field]($field === 'status' ? AssetStatus::tryFrom((string) $oldValue) : $oldValue),
                    $labels[$field]($field === 'status' ? $asset->status : $newValue),
                );

                continue;
            }

            if (in_array($field, ['name', 'model', 'serial_number', 'internal_code', 'brand_id', 'asset_type_id'], true)) {
                $otherFieldsChanged[] = $field;
            }
        }

        if ($otherFieldsChanged !== []) {
            $this->log($asset, MovementType::Edicion, comment: 'Campos actualizados: '.implode(', ', $otherFieldsChanged));
        }
    }
}
