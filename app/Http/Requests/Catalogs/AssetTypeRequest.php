<?php

namespace App\Http\Requests\Catalogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $assetTypeId = $this->route('asset_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('asset_types', 'code')->ignore($assetTypeId),
            ],
            'icon' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? mb_strtoupper($this->code) : $this->code,
        ]);
    }
}
