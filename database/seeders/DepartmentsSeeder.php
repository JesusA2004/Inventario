<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    /**
     * Catálogo global de áreas (company_id null): el modelo actual permite
     * departamentos sin empresa asignada, así que no inventamos una
     * asociación de empresa que no conocemos con certeza.
     */
    public function run(): void
    {
        $names = [
            'Dirección General',
            'Dirección Comercial',
            'Sistemas',
            'Contabilidad',
            'Recursos Humanos',
            'Mesa de Control',
            'Jurídico',
            'Mercadotecnia y Publicidad',
            'Diseño',
            'Coordinación',
            'Coordinación Regional',
            'Call Center',
            'Riesgos',
            'Monitorista',
            'Administrativo',
            'Asistencia de Dirección',
            'Ingeniería de Producto',
            'Arquitectura',
            'Asistencia',
            'Ejecutivo',
            'Sin asignar',
        ];

        foreach ($names as $name) {
            $name = trim(preg_replace('/\s+/', ' ', $name));

            Department::query()->updateOrCreate(
                ['company_id' => null, 'name' => $name],
                ['active' => true],
            );
        }
    }
}
