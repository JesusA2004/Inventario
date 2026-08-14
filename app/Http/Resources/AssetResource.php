<?php

namespace App\Http\Resources;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Asset */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof AssetStatus ? $this->status : AssetStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'internal_code' => $this->internal_code,
            'name' => $this->name,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'status' => $status ? ['value' => $status->value, 'label' => $status->label(), 'color' => $status->color()] : null,
            'in_inventory' => (bool) $this->in_inventory,
            'company' => $this->whenLoaded('company', fn () => ['id' => $this->company->id, 'name' => $this->company->name, 'code' => $this->company->code]),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? ['id' => $this->branch->id, 'name' => $this->branch->name] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? ['id' => $this->department->id, 'name' => $this->department->name] : null),
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? ['id' => $this->brand->id, 'name' => $this->brand->name] : null),
            'assetType' => $this->whenLoaded('assetType', fn () => $this->assetType ? ['id' => $this->assetType->id, 'name' => $this->assetType->name] : null),
            'currentResponsible' => $this->whenLoaded('currentResponsible', fn () => $this->currentResponsible ? ['id' => $this->currentResponsible->id, 'full_name' => $this->currentResponsible->full_name] : null),
            'acquired_at' => $this->acquired_at?->toDateString(),
            'last_reviewed_at' => $this->last_reviewed_at?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),

            // Detail-only fields (ficha del activo)
            'company_id' => $this->when($request->routeIs('assets.show'), $this->company_id),
            'branch_id' => $this->when($request->routeIs('assets.show'), $this->branch_id),
            'department_id' => $this->when($request->routeIs('assets.show'), $this->department_id),
            'asset_type_id' => $this->when($request->routeIs('assets.show'), $this->asset_type_id),
            'brand_id' => $this->when($request->routeIs('assets.show'), $this->brand_id),
            'components' => $this->when($request->routeIs('assets.show'), $this->components),
            'specifications' => $this->when($request->routeIs('assets.show'), $this->specifications),
            'notes' => $this->when($request->routeIs('assets.show'), $this->notes),
            'invoice_url' => $this->when($request->routeIs('assets.show'), $this->invoice_url),
            'purchase_date' => $this->when($request->routeIs('assets.show'), $this->purchase_date?->toDateString()),
            'decommissioned_at' => $this->when($request->routeIs('assets.show'), $this->decommissioned_at?->toDateString()),
            'decommission_reason' => $this->when($request->routeIs('assets.show'), $this->decommission_reason),
            'decommission_notes' => $this->when($request->routeIs('assets.show'), $this->decommission_notes),
            'deliveredByResponsible' => $this->whenLoaded('deliveredByResponsible', fn () => $this->deliveredByResponsible ? ['id' => $this->deliveredByResponsible->id, 'full_name' => $this->deliveredByResponsible->full_name] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null),
            'files' => $this->whenLoaded('files', fn () => $this->files->map(fn ($file) => [
                'id' => $file->id,
                'type' => $file->type->value,
                'type_label' => $file->type->label(),
                'path' => $file->path,
                'original_name' => $file->original_name,
                'mime' => $file->mime,
                'size' => $file->size,
                'uploader' => $file->uploader?->name,
                'created_at' => $file->created_at?->toISOString(),
            ])),
            'movements' => $this->whenLoaded('movements', fn () => $this->movements->map(fn ($movement) => [
                'id' => $movement->id,
                'type' => $movement->type->value,
                'type_label' => $movement->type->label(),
                'field' => $movement->field,
                'old_value' => $movement->old_value,
                'new_value' => $movement->new_value,
                'comment' => $movement->comment,
                'user' => $movement->user?->name,
                'created_at' => $movement->created_at?->toISOString(),
            ])),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review) => [
                'id' => $review->id,
                'reviewed_at' => $review->reviewed_at?->toDateString(),
                'physical_status' => $review->physical_status,
                'location_ok' => (bool) $review->location_ok,
                'responsible_ok' => (bool) $review->responsible_ok,
                'notes' => $review->notes,
                'user' => $review->user?->name,
            ])),
            'loans' => $this->whenLoaded('loans', fn () => $this->loans->map(fn ($loan) => [
                'id' => $loan->id,
                'status' => ['value' => $loan->status->value, 'label' => $loan->status->label(), 'color' => $loan->status->color()],
                'loan_date' => $loan->loan_date?->toDateString(),
                'expected_return_date' => $loan->expected_return_date?->toDateString(),
                'actual_return_date' => $loan->actual_return_date?->toDateString(),
                'assigned_to' => $loan->assignedTo?->full_name,
                'delivered_by' => $loan->deliveredBy?->full_name,
                'received_by' => $loan->receivedBy?->full_name,
                'reason' => $loan->reason,
            ])),
            'parts' => $this->whenLoaded('parts', fn () => $this->parts->map(fn ($part) => [
                'id' => $part->id,
                'public_id' => $part->public_id,
                'name' => $part->name,
                'internal_code' => $part->internal_code,
                'status' => ['value' => $part->status->value, 'label' => $part->status->label(), 'color' => $part->status->color()],
            ])),
        ];
    }
}
