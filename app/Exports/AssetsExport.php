<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * @implements FromCollection<int, Asset>
 * @implements WithMapping<Asset>
 */
class AssetsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  Collection<int, Asset>  $assets
     */
    public function __construct(private readonly Collection $assets) {}

    /**
     * @return Collection<int, Asset>
     */
    public function collection(): Collection
    {
        return $this->assets;
    }

    public function headings(): array
    {
        return [
            'Clave interna', 'Dispositivo', 'Tipo', 'Marca', 'Modelo', 'N° de serie',
            'Empresa', 'Sucursal', 'Área', 'Responsable actual', 'Estatus',
            '¿Sigue en inventario?', 'Fecha de alta', 'Última revisión',
        ];
    }

    public function title(): string
    {
        return 'Datos';
    }

    /**
     * @param  Asset  $asset
     */
    public function map(mixed $asset): array
    {
        return [
            $asset->internal_code,
            $asset->name,
            $asset->assetType?->name,
            $asset->brand?->name,
            $asset->model,
            $asset->serial_number,
            $asset->company?->name,
            $asset->branch?->name,
            $asset->department?->name,
            $asset->currentResponsible?->full_name,
            $asset->status->label(),
            $asset->in_inventory ? 'Sí' : 'No',
            $asset->acquired_at?->format('d/m/Y'),
            $asset->last_reviewed_at?->format('d/m/Y'),
        ];
    }
}
