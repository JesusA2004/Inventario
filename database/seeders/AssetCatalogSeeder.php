<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetCatalogSeeder extends Seeder
{
    /**
     * Minimal starter catalog of asset types, per the spec (section 53):
     * no devices, no companies, no fake inventory — just the base types
     * so the "new asset" form isn't empty on day one.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Equipo de cómputo', 'code' => 'EC', 'icon' => 'monitor'],
            ['name' => 'Laptop', 'code' => 'LAP', 'icon' => 'laptop'],
            ['name' => 'Monitor', 'code' => 'MON', 'icon' => 'monitor'],
            ['name' => 'Impresora', 'code' => 'IMP', 'icon' => 'printer'],
            ['name' => 'Tablet', 'code' => 'TAB', 'icon' => 'tablet'],
            ['name' => 'Teléfono', 'code' => 'TEL', 'icon' => 'smartphone'],
            ['name' => 'Access Point', 'code' => 'AP', 'icon' => 'wifi'],
            ['name' => 'Router', 'code' => 'ROU', 'icon' => 'router'],
            ['name' => 'Switch', 'code' => 'SW', 'icon' => 'network'],
            ['name' => 'DVR', 'code' => 'DVR', 'icon' => 'video'],
            ['name' => 'NVR', 'code' => 'NVR', 'icon' => 'video'],
            ['name' => 'Cámara', 'code' => 'CAM', 'icon' => 'camera'],
            ['name' => 'UPS / No Break', 'code' => 'UPS', 'icon' => 'battery-charging'],
            ['name' => 'Periférico', 'code' => 'PER', 'icon' => 'mouse'],
            ['name' => 'Otro', 'code' => 'OTR', 'icon' => 'box'],
        ];

        foreach ($types as $type) {
            AssetType::query()->firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
