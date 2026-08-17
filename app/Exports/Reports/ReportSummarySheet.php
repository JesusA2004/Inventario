<?php

namespace App\Exports\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * "Resumen" sheet added to every report's Excel export, next to the raw
 * "Datos" sheet: same filters/KPIs/breakdown shown on screen and in the PDF,
 * so the three outputs (pantalla, PDF, Excel) always carry the same
 * information even though this one is a plain table instead of a chart.
 */
class ReportSummarySheet implements FromArray, WithTitle
{
    /**
     * @param  array<int, array{label: string, value: int|string}>  $kpis
     * @param  array{title: string, items: Collection<int, array{label: string, total: int}>}|null  $breakdown
     */
    public function __construct(
        private readonly string $reportTitle,
        private readonly string $generatedAt,
        private readonly string $generatedBy,
        private readonly string $filtersSummary,
        private readonly array $kpis,
        private readonly ?array $breakdown,
        private readonly int $total,
    ) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        $rows = [
            ['Reporte', $this->reportTitle],
            ['Generado', $this->generatedAt],
            ['Por', $this->generatedBy],
            ['Filtros', $this->filtersSummary !== '' ? $this->filtersSummary : 'Sin filtros'],
            ['Total de registros', $this->total],
        ];

        if ($this->kpis !== []) {
            $rows[] = [];
            $rows[] = ['Indicadores clave'];

            foreach ($this->kpis as $kpi) {
                $rows[] = [$kpi['label'], $kpi['value']];
            }
        }

        if ($this->breakdown && $this->breakdown['items']->count() > 0) {
            $rows[] = [];
            $rows[] = [$this->breakdown['title']];
            $rows[] = ['Categoría', 'Total'];

            foreach ($this->breakdown['items'] as $item) {
                $rows[] = [$item['label'], $item['total']];
            }
        }

        return $rows;
    }
}
