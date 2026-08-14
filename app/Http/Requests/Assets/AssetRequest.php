<?php

namespace App\Http\Requests\Assets;

use App\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AssetRequest extends FormRequest
{
    public function rules(): array
    {
        $assetId = $this->route('asset')?->id;

        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'asset_type_id' => ['required', 'integer', 'exists:asset_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'internal_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('assets', 'internal_code')->ignore($assetId),
            ],
            'status' => ['required', new Enum(AssetStatus::class)],
            'in_inventory' => ['boolean'],
            'current_responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'delivered_by_responsible_id' => ['nullable', 'integer', 'exists:responsible_people,id'],
            'components' => ['nullable', 'string', 'max:5000'],
            'specifications' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000', function ($attribute, $value, $fail) {
                if ($value && preg_match('/(contrase[ñn]a|password|pwd|wifi)\s*[:=]/i', (string) $value)) {
                    $fail('No guardes contraseñas ni credenciales en las observaciones. Usa un gestor de contraseñas seguro.');
                }
            }],
            'invoice_url' => ['nullable', 'url', 'max:500'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'max:5120'],
            'purchase_date' => ['nullable', 'date'],
            'acquired_at' => ['required', 'date'],
            'last_reviewed_at' => ['nullable', 'date'],
        ];
    }
}
