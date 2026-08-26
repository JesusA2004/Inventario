<?php

namespace App\Http\Requests\Catalogs;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class BrandRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('name')) {
                return;
            }

            $routeBrand = $this->route('brand');
            $brandId = $routeBrand instanceof Brand ? $routeBrand->id : null;
            $slug = Str::slug($this->string('name'));

            $exists = Brand::query()
                ->where('slug', $slug)
                ->when($brandId, fn (Builder $query) => $query->where('id', '!=', $brandId))
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'Ya existe una marca con este nombre.');
            }
        });
    }
}
