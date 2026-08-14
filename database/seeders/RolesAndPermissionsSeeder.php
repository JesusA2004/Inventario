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
        ]);

        $auditor = Role::findOrCreate('auditor', 'web');
        $auditor->syncPermissions([
            'ver-activos', 'ver-piezas', 'ver-prestamos',
            'ver-auditorias', 'gestionar-auditorias',
            'ver-reportes', 'ver-catalogos',
        ]);

        $consulta = Role::findOrCreate('consulta', 'web');
        $consulta->syncPermissions([
            'ver-activos', 'ver-piezas', 'ver-prestamos', 'ver-auditorias', 'ver-reportes', 'ver-catalogos',
        ]);
    }
}
