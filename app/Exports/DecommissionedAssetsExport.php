<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements FromCollection<int, Asset>
 * @implements WithMapping<Asset>
 */
class DecommissionedAssetsExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Clave interna', 'Dispositivo', 'Empresa', 'Sucursal', 'Fecha de baja', 'Motivo', 'Observaciones'];
    }

    /**
     * @param  Asset  $asset
     */
    public function map(mixed $asset): array
    {
        return [
            $asset->internal_code,
            $asset->name,
            $asset->company?->name,
            $asset->branch?->name,
            $asset->decommissioned_at?->format('d/m/Y'),
            $asset->decommission_reason,
            $asset->decommission_notes,
        ];
    }
}
