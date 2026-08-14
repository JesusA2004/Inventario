<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminCommand extends Command
{
    protected $signature = 'inventario:crear-admin
        {--name= : Nombre completo del administrador}
        {--email= : Correo electrónico del administrador}
        {--password= : Contraseña (si se omite, se solicitará de forma oculta)}';

    protected $description = 'Crea la primera cuenta de superadministrador del sistema (uso manual, no se ejecuta en el seeder)';

    public function handle(): int
    {
        if (! Role::where('name', 'superadministrador')->exists()) {
            $this->error('El rol "superadministrador" no existe todavía. Ejecuta primero "php artisan db:seed".');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Nombre completo');
        $email = $this->option('email') ?: $this->ask('Correo electrónico');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 10 caracteres)');
        $confirmation = $this->option('password') ? $password : $this->secret('Confirma la contraseña');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:10'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($password !== $confirmation) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $admin->assignRole('superadministrador');

        $this->info("Administrador creado correctamente: {$email}");

        return self::SUCCESS;
    }
}
