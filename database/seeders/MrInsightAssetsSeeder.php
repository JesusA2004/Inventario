<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\ResponsiblePerson;
use App\Services\AssetMovementService;
use Illuminate\Database\Seeder;

class MrInsightAssetsSeeder extends Seeder
{
    /**
     * Carga el inventario inicial (56 activos) de MR INSIGHT, todos bajo
     * Empresa MR INSIGHT / Sucursal Corporativo. La fuente de datos vive en
     * database/data/mr-insight-assets.php (nunca un XLSX).
     *
     * Idempotente: cada activo se identifica por su internal_code (único
     * globalmente), así que volver a ejecutar el seeder actualiza los
     * mismos 56 registros en vez de duplicarlos, y nunca regenera su
     * public_id (el modelo Asset solo lo asigna una vez, al crear).
     */
    public function run(): void
    {
        $company = Company::query()->where('code', 'MRI')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'CORP')->firstOrFail();

        $assetTypes = AssetType::query()->pluck('id', 'name');
        $brands = Brand::query()->pluck('id', 'name');
        $responsiblePeople = ResponsiblePerson::query()
            ->where('company_id', $company->id)
            ->pluck('id', 'full_name');

        $rows = require database_path('data/mr-insight-assets.php');

        $movements = app(AssetMovementService::class);

        foreach ($rows as $row) {
            $assetTypeId = $assetTypes[$row['asset_type']] ?? null;
            abort_if($assetTypeId === null, 500, "Tipo de activo no encontrado en el catálogo: {$row['asset_type']}");

            $brandId = $row['brand'] ? ($brands[$row['brand']] ?? null) : null;
            abort_if($row['brand'] && $brandId === null, 500, "Marca no encontrada en el catálogo: {$row['brand']}");

            $responsibleId = $row['responsible'] ? ($responsiblePeople[$row['responsible']] ?? null) : null;
            abort_if($row['responsible'] && $responsibleId === null, 500, "Responsable no encontrado: {$row['responsible']}");

            $status = AssetStatus::from($row['status']);

            $asset = Asset::query()->where('internal_code', $row['internal_code'])->first();
            $isNew = $asset === null;

            $asset = Asset::query()->updateOrCreate(
                ['internal_code' => $row['internal_code']],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'department_id' => null,
                    'asset_type_id' => $assetTypeId,
                    'name' => $row['name'],
                    'brand_id' => $brandId,
                    'model' => $row['model'],
                    'serial_number' => $row['serial_number'],
                    'status' => $status,
                    'in_inventory' => $status !== AssetStatus::Baja,
                    'current_responsible_id' => $responsibleId,
                    'notes' => $row['notes'],
                    'acquired_at' => $row['acquired_at'],
                ],
            );

            if ($isNew) {
                $movements->log($asset, MovementType::Alta, comment: 'Alta inicial del inventario de MR INSIGHT.');
            }
        }
    }
}
