<?php

namespace App\Exports;

use App\Enums\AuditItemStatus;
use App\Models\Audit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * @implements FromCollection<int, Audit>
 * @implements WithMapping<Audit>
 */
class AuditsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  Collection<int, Audit>  $audits
     */
    public function __construct(private readonly Collection $audits) {}

    /**
     * @return Collection<int, Audit>
     */
    public function collection(): Collection
    {
        return $this->audits;
    }

    public function headings(): array
    {
        return ['Nombre', 'Empresa', 'Sucursal', 'Área', 'Estatus', 'Iniciada', 'Finalizada', 'Esperados', 'Encontrados', 'No encontrados'];
    }

    public function title(): string
    {
        return 'Datos';
    }

    /**
     * @param  Audit  $audit
     */
    public function map(mixed $audit): array
    {
        return [
            $audit->name,
            $audit->company?->name,
            $audit->branch?->name,
            $audit->department?->name,
            $audit->status->label(),
            $audit->started_at?->format('d/m/Y H:i'),
            $audit->finished_at?->format('d/m/Y H:i'),
            $audit->items->count(),
            $audit->items->where('status', AuditItemStatus::Encontrado)->count(),
            $audit->items->where('status', AuditItemStatus::NoEncontrado)->count(),
        ];
    }
}
