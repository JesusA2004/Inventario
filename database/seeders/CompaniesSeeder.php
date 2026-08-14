<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompaniesSeeder extends Seeder
{
    /**
     * Las tres empresas son independientes entre sí: MR INSIGHT y
     * SOLARIEGA CENIT no pertenecen a MR LANA. Idempotente por "code".
     */
    public function run(): void
    {
        $companies = [
            ['name' => 'MR LANA', 'code' => 'CML'],
            ['name' => 'SOLARIEGA CENIT', 'code' => 'SC'],
            ['name' => 'MR INSIGHT', 'code' => 'MRI'],
        ];

        foreach ($companies as $company) {
            Company::query()->updateOrCreate(
                ['code' => $company['code']],
                ['name' => $company['name'], 'active' => true],
            );
        }
    }
}
