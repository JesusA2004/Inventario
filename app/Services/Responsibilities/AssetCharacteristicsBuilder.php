<?php

namespace App\Services\Responsibilities;

use App\Enums\ResponsibilityProfile;
use App\Models\Asset;

/**
 * Arma la tabla "Nombre de la característica | Descripción detallada" que se
 * precarga en la responsiva. Solo usa datos ya existentes en el activo
 * (nunca inventa valores). El renglón siempre queda editable a mano después,
 * en la ficha de la responsiva (ver AssetResponsibilityService::updateCharacteristics()).
 */
class AssetCharacteristicsBuilder
{
    /**
     * @return array<int, array{name: string, value: string}>
     */
    public function build(Asset $asset): array
    {
        $profile = $asset->assetType->responsibility_profile ?? ResponsibilityProfile::General;

        return $profile === ResponsibilityProfile::Computer
            ? $this->buildComputerRows($asset)
            : $this->buildGenericRows($asset);
    }

    /**
     * Estructura exacta del formato real de responsiva de equipo de cómputo:
     * "Equipo 1 / Marca / Modelo / Procesador / Memoria RAM / Almacenamiento
     * / Sistema operativo / Estado". Procesador, RAM, almacenamiento y
     * sistema operativo no existen como columnas estructuradas en Asset, así
     * que quedan vacíos por diseño -nunca inventados- para completarse a
     * mano al editar la responsiva, en vez de forzar un valor incorrecto.
     *
     * @return array<int, array{name: string, value: string}>
     */
    private function buildComputerRows(Asset $asset): array
    {
        $status = $asset->status;

        return [
            ['name' => 'Equipo 1', 'value' => $asset->name],
            ['name' => 'Marca', 'value' => $asset->brand?->name ?? ''],
            ['name' => 'Modelo', 'value' => $asset->model ?? ''],
            ['name' => 'Procesador', 'value' => ''],
            ['name' => 'Memoria RAM', 'value' => ''],
            ['name' => 'Almacenamiento', 'value' => ''],
            ['name' => 'Sistema operativo', 'value' => ''],
            ['name' => 'Estado', 'value' => $status->label()],
        ];
    }

    /**
     * Formato genérico previo, para cualquier perfil que no sea "equipo de
     * cómputo" (móvil, red, periférico, general): sin cambios de
     * comportamiento respecto a lo que ya existía.
     *
     * @return array<int, array{name: string, value: string}>
     */
    private function buildGenericRows(Asset $asset): array
    {
        $status = $asset->status;

        $rows = [
            ['name' => 'Equipo', 'value' => $asset->name],
            ['name' => 'Clave interna', 'value' => $asset->internal_code],
            ['name' => 'Tipo', 'value' => $asset->assetType->name],
            ['name' => 'Marca', 'value' => $asset->brand?->name ?? '—'],
            ['name' => 'Modelo', 'value' => $asset->model ?: '—'],
            ['name' => 'Número de serie', 'value' => $asset->serial_number ?: '—'],
            ['name' => 'Estado', 'value' => $status->label()],
        ];

        if (filled($asset->components)) {
            $rows[] = ['name' => 'Componentes', 'value' => $asset->components];
        }

        if (filled($asset->specifications)) {
            $rows[] = ['name' => 'Especificaciones', 'value' => $asset->specifications];
        }

        return $rows;
    }
}
