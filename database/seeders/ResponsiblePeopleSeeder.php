<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ResponsiblePeopleSeeder extends Seeder
{
    /**
     * Únicamente los responsables necesarios para relacionar los activos de
     * MR INSIGHT. No se precargan responsables de MR LANA ni SOLARIEGA
     * CENIT: esos se capturan desde cero en la aplicación.
     */
    public function run(): void
    {
        $mrInsight = Company::query()->where('code', 'MRI')->firstOrFail();
        $corporativo = Branch::query()
            ->where('company_id', $mrInsight->id)
            ->where('code', 'CORP')
            ->firstOrFail();

        $names = ['Frida Hernandez', 'Shanik Lopez', 'Ariel Jael', 'Betsabe Perez'];

        foreach ($names as $fullName) {
            $mrInsight->responsiblePeople()->updateOrCreate(
                ['company_id' => $mrInsight->id, 'full_name' => $fullName],
                ['branch_id' => $corporativo->id, 'department_id' => null, 'active' => true],
            );
        }
    }
}
