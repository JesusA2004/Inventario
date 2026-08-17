<?php

namespace App\Exports\Reports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Wraps a type-specific data export (AssetsExport, LoansExport, etc.) plus
 * the shared ReportSummarySheet into a single .xlsx with two tabs: "Datos"
 * (the raw rows, as before) and "Resumen" (KPIs/filtros/breakdown).
 */
class ReportExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly object $dataSheet,
        private readonly ReportSummarySheet $summarySheet,
    ) {}

    public function sheets(): array
    {
        return [
            'Datos' => $this->dataSheet,
            'Resumen' => $this->summarySheet,
        ];
    }
}
