<?php

namespace App\Http\Requests\Catalogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('companies', 'code')->ignore($companyId),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
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
