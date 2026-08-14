<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetTypesSeeder extends Seeder
{
    /**
     * Catálogo inicial de tipos de activo. Idempotente por "code" vía
     * firstOrCreate: cada ejecución adicional de db:seed no duplica tipos.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Equipo', 'code' => 'EQP', 'icon' => 'box'],
            ['name' => 'Equipo de cómputo', 'code' => 'EC', 'icon' => 'monitor'],
            ['name' => 'Laptop', 'code' => 'LAP', 'icon' => 'laptop'],
            ['name' => 'Monitor', 'code' => 'MON', 'icon' => 'monitor'],
            ['name' => 'Celular', 'code' => 'CEL', 'icon' => 'smartphone'],
            ['name' => 'Teléfono', 'code' => 'TEL', 'icon' => 'smartphone'],
            ['name' => 'Tablet', 'code' => 'TAB', 'icon' => 'tablet'],
            ['name' => 'Impresora', 'code' => 'IMP', 'icon' => 'printer'],
            ['name' => 'Equipo de video', 'code' => 'EV', 'icon' => 'video'],
            ['name' => 'Equipo de audio', 'code' => 'EA', 'icon' => 'headphones'],
            ['name' => 'Accesorio', 'code' => 'ACC', 'icon' => 'plug'],
            ['name' => 'Accesorios', 'code' => 'ACCS', 'icon' => 'plug'],
            ['name' => 'Mobiliario', 'code' => 'MOB', 'icon' => 'armchair'],
            ['name' => 'Periférico', 'code' => 'PER', 'icon' => 'mouse'],
            ['name' => 'Pieza / Refacción', 'code' => 'PZA', 'icon' => 'wrench'],
            ['name' => 'Ext / Servicio', 'code' => 'EXT', 'icon' => 'shield-check'],
            ['name' => 'Router', 'code' => 'ROU', 'icon' => 'router'],
            ['name' => 'Switch', 'code' => 'SW', 'icon' => 'network'],
            ['name' => 'Access Point', 'code' => 'AP', 'icon' => 'wifi'],
            ['name' => 'DVR', 'code' => 'DVR', 'icon' => 'video'],
            ['name' => 'NVR', 'code' => 'NVR', 'icon' => 'video'],
            ['name' => 'Cámara', 'code' => 'CAM', 'icon' => 'camera'],
            ['name' => 'UPS / No Break', 'code' => 'UPS', 'icon' => 'battery-charging'],
            ['name' => 'Otro', 'code' => 'OTR', 'icon' => 'box'],
        ];

        foreach ($types as $type) {
            AssetType::query()->firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
