<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Solo catálogos base + el inventario inicial de MR INSIGHT. No crea
     * cuentas de usuario: el primer administrador se crea con
     * `php artisan inventario:crear-admin`. Todos los seeders son
     * idempotentes (correr `db:seed` varias veces no duplica nada).
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CompaniesSeeder::class,
            BranchesSeeder::class,
            DepartmentsSeeder::class,
            BrandsSeeder::class,
            AssetTypesSeeder::class,
            ResponsiblePeopleSeeder::class,
            MrInsightAssetsSeeder::class,
        ]);
    }
}
