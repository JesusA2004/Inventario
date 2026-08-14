<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchesSeeder extends Seeder
{
    /**
     * Solo se precargan las sucursales que conocemos con certeza: el
     * Corporativo de MR LANA y el Corporativo de MR INSIGHT. Son dos
     * registros distintos (distinto company_id) aunque compartan nombre y
     * código "CORP" — la unicidad de branches es (company_id, code), nunca
     * global, así que esto es válido por diseño.
     *
     * SOLARIEGA CENIT no tiene sucursal conocida todavía: se captura desde
     * la aplicación, no se inventa aquí.
     */
    public function run(): void
    {
        $mrLana = Company::query()->where('code', 'CML')->firstOrFail();
        $mrInsight = Company::query()->where('code', 'MRI')->firstOrFail();

        foreach ([$mrLana, $mrInsight] as $company) {
            $company->branches()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CORP'],
                ['name' => 'Corporativo', 'active' => true],
            );
        }
    }
}
