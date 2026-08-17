<?php

namespace App\Exports;

use App\Models\Loan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * @implements FromCollection<int, Loan>
 * @implements WithMapping<Loan>
 */
class LoansExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  Collection<int, Loan>  $loans
     */
    public function __construct(private readonly Collection $loans) {}

    /**
     * @return Collection<int, Loan>
     */
    public function collection(): Collection
    {
        return $this->loans;
    }

    public function headings(): array
    {
        return ['Activo', 'Empresa', 'Asignado a', 'Entregó', 'Recibió', 'Fecha de salida', 'Devolución esperada', 'Devolución real', 'Estatus', 'Motivo'];
    }

    public function title(): string
    {
        return 'Datos';
    }

    /**
     * @param  Loan  $loan
     */
    public function map(mixed $loan): array
    {
        return [
            $loan->asset?->internal_code,
            $loan->company?->name,
            $loan->assignedTo?->full_name,
            $loan->deliveredBy?->full_name,
            $loan->receivedBy?->full_name,
            $loan->loan_date?->format('d/m/Y'),
            $loan->expected_return_date?->format('d/m/Y'),
            $loan->actual_return_date?->format('d/m/Y'),
            $loan->status->label(),
            $loan->reason,
        ];
    }
}
