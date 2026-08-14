<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandsSeeder extends Seeder
{
    /**
     * Catálogo normalizado de marcas. Cada marca existe una sola vez con su
     * capitalización canónica (p. ej. "Apple", nunca "APPLE" y "Apple" como
     * registros distintos); el mapeo de variantes del dataset original
     * (SAMSING -> Samsung, etc.) se resuelve al importar los activos de
     * MR INSIGHT, no aquí.
     */
    public function run(): void
    {
        $names = [
            'Acteck', 'ADATA', 'Apple', 'ASUS', 'ARZOPA', 'BenQ', 'Cetttech',
            'Dell', 'DJI', 'Dorcy', 'Ensamblado', 'Epson', 'Steren', 'Famall',
            'Genérico', 'HP', 'Huawei', 'Ingressio', 'Intel', 'Jabra', 'JBL',
            'Langsdom', 'LG', 'Logitech', 'Microsoft', 'Neewer', 'Nextep',
            'Samsung', 'Sharp', 'Sin marca', 'Sony', 'Starlink', 'Thermaltake',
            'TP-Link Omada', 'Ubiquiti', 'Yeyian', 'Zebra', 'Alfa',
        ];

        foreach ($names as $name) {
            Brand::query()->firstOrCreate(['name' => $name], ['active' => true]);
        }
    }
}
