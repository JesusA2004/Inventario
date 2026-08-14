<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Solo roles, permisos y catálogos base. No crea cuentas de usuario:
     * el primer administrador se crea con `php artisan inventario:crear-admin`.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AssetCatalogSeeder::class,
        ]);
    }
}
