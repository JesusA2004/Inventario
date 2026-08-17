<?php

namespace App\Exports;

use App\Models\Part;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * @implements FromCollection<int, Part>
 * @implements WithMapping<Part>
 */
class PartsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  Collection<int, Part>  $parts
     */
    public function __construct(private readonly Collection $parts) {}

    /**
     * @return Collection<int, Part>
     */
    public function collection(): Collection
    {
        return $this->parts;
    }

    public function headings(): array
    {
        return ['Clave interna', 'Pieza', 'Marca', 'N° de serie', 'Número de parte', 'Estatus', 'Cantidad', 'Ensamblada', 'Activo relacionado', 'Empresa'];
    }

    public function title(): string
    {
        return 'Datos';
    }

    /**
     * @param  Part  $part
     */
    public function map(mixed $part): array
    {
        return [
            $part->internal_code,
            $part->name,
            $part->brand?->name,
            $part->serial_number,
            $part->part_number,
            $part->status->label(),
            $part->quantity,
            $part->assembled ? 'Sí' : 'No',
            $part->relatedAsset?->internal_code,
            $part->company?->name,
        ];
    }
}
