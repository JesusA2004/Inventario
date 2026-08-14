<?php

namespace App\Http\Requests\Parts;

use App\Enums\PartStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PartRequest extends FormRequest
{
    public function rules(): array
    {
        $partId = $this->route('part')?->id;

        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'internal_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parts', 'internal_code')->ignore($partId),
            ],
            'related_asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', new Enum(PartStatus::class)],
            'in_inventory' => ['boolean'],
            'quantity' => ['required', 'integer', 'min:1'],
            'specifications' => ['nullable', 'string', 'max:2000'],
            'assembled' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'purchase_date' => ['nullable', 'date'],
            'responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'invoice_url' => ['nullable', 'url', 'max:500'],
            'needs_label' => ['boolean'],
        ];
    }
}
