<?php

namespace App\Http\Requests\Parts;

use App\Enums\PartStatus;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\ResponsiblePerson;
use Illuminate\Contracts\Validation\Validator;
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

    /**
     * company_id/branch_id son opcionales en piezas, pero cuando sí se
     * captura una empresa, sucursal/responsable/activo relacionado deben
     * pertenecer a esa misma empresa.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $companyId = $this->integer('company_id') ?: null;

            if (! $companyId) {
                return;
            }

            if ($branchId = $this->integer('branch_id')) {
                $branch = Branch::find($branchId);

                if ($branch && $branch->company_id !== $companyId) {
                    $validator->errors()->add('branch_id', 'La sucursal seleccionada no pertenece a la empresa elegida.');
                }
            }

            if ($responsibleId = $this->integer('responsible_id')) {
                $responsible = ResponsiblePerson::find($responsibleId);

                if ($responsible && $responsible->company_id !== $companyId) {
                    $validator->errors()->add('responsible_id', 'El responsable seleccionado no pertenece a la empresa elegida.');
                }
            }

            if ($relatedAssetId = $this->integer('related_asset_id')) {
                $relatedAsset = Asset::find($relatedAssetId);

                if ($relatedAsset && $relatedAsset->company_id !== $companyId) {
                    $validator->errors()->add('related_asset_id', 'El activo relacionado no pertenece a la empresa elegida.');
                }
            }
        });
    }
}
