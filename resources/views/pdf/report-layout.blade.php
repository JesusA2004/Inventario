<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 16mm 12mm; }
        body { font-family: "DejaVu Sans", sans-serif; color: #0f172a; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 2mm; color: #0f2a4a; }
        .meta { color: #475569; font-size: 9px; margin-bottom: 4mm; }
        .meta strong { color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #0f2a4a; color: #fff; text-align: left; padding: 2mm; font-size: 8.5px;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        tbody td { padding: 1.8mm 2mm; border-bottom: 0.5pt solid #e2e8f0; font-size: 9px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .totals { margin-top: 4mm; font-size: 10px; font-weight: bold; color: #0f2a4a; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; }

        /* Resumen / KPIs (equivalente en PDF a las tarjetas y gráficas que se ven en pantalla) */
        table.kpis { width: 100%; margin-bottom: 4mm; border-collapse: separate; border-spacing: 2mm 0; }
        .kpi-cell {
            background: #f1f5f9; border: 0.5pt solid #dbe3ee; border-radius: 2mm; padding: 3mm 2mm;
            text-align: center; width: 1%;
        }
        .kpi-value { font-size: 15px; font-weight: bold; color: #0f2a4a; }
        .kpi-label { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.2px; margin-top: 1mm; }

        .breakdown { margin-bottom: 5mm; }
        .breakdown-title { font-size: 10px; font-weight: bold; color: #0f2a4a; margin-bottom: 2mm; }
        .bar-row { display: table; width: 100%; margin-bottom: 1.3mm; }
        .bar-label { display: table-cell; width: 32mm; font-size: 8.5px; color: #334155; vertical-align: middle; padding-right: 2mm; }
        .bar-track { display: table-cell; vertical-align: middle; }
        .bar-fill { height: 3mm; background: #2f6690; border-radius: 1mm; }
        .bar-value { display: table-cell; width: 10mm; text-align: right; font-size: 8.5px; color: #0f2a4a; font-weight: bold; vertical-align: middle; padding-left: 2mm; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        <strong>Generado:</strong> {{ $generatedAt }} &nbsp;·&nbsp;
        <strong>Por:</strong> {{ $generatedBy }} @if($filtersSummary)&nbsp;·&nbsp; <strong>Filtros:</strong> {{ $filtersSummary }} @endif
    </div>

    @isset($kpis)
        @include('pdf.reports._summary', ['kpis' => $kpis, 'breakdown' => $breakdown ?? null])
    @endisset

    {!! $content !!}

    <div class="totals">Total de registros: {{ $total }}</div>
</body>
</html>
