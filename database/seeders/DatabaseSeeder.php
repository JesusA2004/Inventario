<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AssetCatalogSeeder::class,
        ]);

        if (! User::query()->where('email', 'jesusarizmendimaya@gmail.com')->exists()) {
            $password = Str::password(14);

            $admin = User::factory()->create([
                'name' => 'Jesús Arizmendi',
                'email' => 'jesusarizmendimaya@gmail.com',
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $admin->assignRole('superadministrador');

            $this->command?->warn("Usuario administrador creado -> email: jesusarizmendimaya@gmail.com | contraseña temporal: {$password}");
        }
    }
}
