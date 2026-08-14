<?php

namespace App\Http\Resources;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Loan */
class LoanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOverdue = $this->status === LoanStatus::Prestado
            && $this->expected_return_date !== null
            && $this->expected_return_date->isPast();

        return [
            'id' => $this->id,
            'status' => [
                'value' => $isOverdue ? 'vencido' : $this->status->value,
                'label' => $isOverdue ? 'Vencido' : $this->status->label(),
                'color' => $isOverdue ? 'red' : $this->status->color(),
            ],
            'reason' => $this->reason,
            'loan_date' => $this->loan_date?->toDateString(),
            'expected_return_date' => $this->expected_return_date?->toDateString(),
            'actual_return_date' => $this->actual_return_date?->toDateString(),
            'delivered_confirmed' => (bool) $this->delivered_confirmed,
            'received_confirmed' => (bool) $this->received_confirmed,
            'return_notes' => $this->return_notes,
            'asset' => $this->whenLoaded('asset', fn () => [
                'id' => $this->asset->id,
                'public_id' => $this->asset->public_id,
                'internal_code' => $this->asset->internal_code,
                'name' => $this->asset->name,
            ]),
            'company' => $this->whenLoaded('company', fn () => ['id' => $this->company->id, 'name' => $this->company->name]),
            'assignedTo' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo?->full_name),
            'deliveredBy' => $this->whenLoaded('deliveredBy', fn () => $this->deliveredBy?->full_name),
            'receivedBy' => $this->whenLoaded('receivedBy', fn () => $this->receivedBy?->full_name),
        ];
    }
}
