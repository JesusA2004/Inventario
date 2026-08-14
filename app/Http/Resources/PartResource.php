<?php

namespace App\Http\Resources;

use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Part */
class PartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'internal_code' => $this->internal_code,
            'name' => $this->name,
            'part_number' => $this->part_number,
            'serial_number' => $this->serial_number,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label(), 'color' => $this->status->color()],
            'in_inventory' => (bool) $this->in_inventory,
            'assembled' => (bool) $this->assembled,
            'quantity' => $this->quantity,
            'needs_label' => (bool) $this->needs_label,
            'company' => $this->whenLoaded('company', fn () => $this->company ? ['id' => $this->company->id, 'name' => $this->company->name] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? ['id' => $this->branch->id, 'name' => $this->branch->name] : null),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? ['id' => $this->brand->id, 'name' => $this->brand->name] : null),
            'relatedAsset' => $this->whenLoaded('relatedAsset', fn () => $this->relatedAsset ? ['id' => $this->relatedAsset->id, 'public_id' => $this->relatedAsset->public_id, 'internal_code' => $this->relatedAsset->internal_code] : null),
            'responsible' => $this->whenLoaded('responsible', fn () => $this->responsible ? ['id' => $this->responsible->id, 'full_name' => $this->responsible->full_name] : null),
            'specifications' => $this->specifications,
            'notes' => $this->notes,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'decommissioned_at' => $this->decommissioned_at?->toDateString(),
            'decommission_reason' => $this->decommission_reason,
            'invoice_url' => $this->invoice_url,
        ];
    }
}
