<?php

namespace App\Http\Resources;

use App\Models\AssetMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssetMovement */
class AssetMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'field' => $this->field,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => $this->user?->name),
            'asset' => $this->whenLoaded('asset', fn () => [
                'public_id' => $this->asset->public_id,
                'internal_code' => $this->asset->internal_code,
                'name' => $this->asset->name,
                'company' => $this->asset->relationLoaded('company') ? $this->asset->company?->name : null,
                'branch' => $this->asset->relationLoaded('branch') ? $this->asset->branch?->name : null,
            ]),
        ];
    }
}
