<?php

namespace App\Http\Requests\Catalogs;

use App\Enums\ResponsibilityProfile;
use App\Models\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AssetTypeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeAssetType = $this->route('asset_type');
        $assetTypeId = $routeAssetType instanceof AssetType ? $routeAssetType->id : null;

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
            'responsibility_profile' => ['required', new Enum(ResponsibilityProfile::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? mb_strtoupper($this->code) : $this->code,
            // Por defecto "general" si el formulario no lo manda (p. ej.
            // llamadas antiguas/externas que no conocen este campo todavía).
            'responsibility_profile' => $this->responsibility_profile ?: 'general',
        ]);
    }
}
