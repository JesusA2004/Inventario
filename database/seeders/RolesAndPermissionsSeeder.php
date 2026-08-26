<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ver-activos', 'crear-activos', 'editar-activos', 'dar-de-baja-activos',
            'ver-piezas', 'gestionar-piezas',
            'ver-prestamos', 'gestionar-prestamos',
            'ver-auditorias', 'gestionar-auditorias',
            'ver-reportes',
            'ver-catalogos', 'gestionar-catalogos',
            'gestionar-usuarios',
            'gestionar-configuracion',
            'ver-responsivas', 'crear-responsivas', 'enviar-responsivas',
            'gestionar-responsivas', 'ver-evidencia-responsivas', 'configurar-responsivas',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superadmin = Role::findOrCreate('superadministrador', 'web');
        $superadmin->syncPermissions($permissions);

        $sistemas = Role::findOrCreate('sistemas', 'web');
        $sistemas->syncPermissions([
            'ver-activos', 'crear-activos', 'editar-activos', 'dar-de-baja-activos',
            'ver-piezas', 'gestionar-piezas',
            'ver-prestamos', 'gestionar-prestamos',
            'ver-auditorias', 'gestionar-auditorias',
            'ver-reportes',
            'ver-catalogos', 'gestionar-catalogos',
            // "gestionar" se incluye además de ver/crear/enviar porque, en la
            // operación diaria, Sistemas necesita poder reenviar, cancelar y
            // subir copias físicas de responsivas (no solo verlas/crearlas).
            'ver-responsivas', 'crear-responsivas', 'enviar-responsivas', 'gestionar-responsivas',
        ]);

        $auditor = Role::findOrCreate('auditor', 'web');
        $auditor->syncPermissions([
            'ver-activos', 'ver-piezas', 'ver-prestamos',
            'ver-auditorias', 'gestionar-auditorias',
            'ver-reportes', 'ver-catalogos',
            'ver-responsivas',
        ]);

        $consulta = Role::findOrCreate('consulta', 'web');
        $consulta->syncPermissions([
            'ver-activos', 'ver-piezas', 'ver-prestamos', 'ver-auditorias', 'ver-reportes', 'ver-catalogos',
        ]);
    }
}
