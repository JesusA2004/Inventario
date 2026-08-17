<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiquetas QR</title>
    <style>
        @page { margin: 12mm 8mm; }
        body { font-family: "DejaVu Sans", sans-serif; color: #0f172a; }
        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.grid td { padding: 3mm; vertical-align: top; }
        .label {
            width: {{ $dimensions['width'] }}mm;
            min-height: {{ $dimensions['height'] }}mm;
            margin: 0 auto;
            border: 0.75pt solid #94a3b8;
            border-radius: 3px;
            padding: 3mm;
            text-align: center;
            page-break-inside: avoid;
        }
        .label .type { font-size: {{ $dimensions['font_type'] }}pt; font-weight: bold; letter-spacing: 0.5pt; text-transform: uppercase; color: #1e293b; }
        .label .code { font-size: {{ $dimensions['font_code'] }}pt; font-weight: bold; margin: 1.5mm 0; color: #0f172a; }
        .label .qr { width: {{ $dimensions['qr'] }}mm; height: {{ $dimensions['qr'] }}mm; margin: 1mm auto; }
        .label .serial { font-size: {{ $dimensions['font_small'] }}pt; color: #334155; margin-top: 1mm; }
        .label .company { font-size: {{ $dimensions['font_small'] }}pt; font-weight: bold; color: #1e3a5f; margin-top: 0.5mm; }
    </style>
</head>
<body>
    <table class="grid">
        @foreach ($labels->chunk($columns) as $row)
            <tr>
                @foreach ($row as $label)
                    <td style="width: {{ number_format(100 / $columns, 2) }}%;">
                        <div class="label">
                            <div class="type">{{ $label['type_name'] }}</div>
                            <div class="code">{{ $label['internal_code'] }}</div>
                            <img class="qr" src="{{ $label['qr'] }}" alt="QR">
                            @if ($label['serial_number'])
                                <div class="serial">S/N: {{ $label['serial_number'] }}</div>
                            @endif
                            <div class="company">{{ $label['company_name'] }}</div>
                        </div>
                    </td>
                @endforeach
                @for ($i = $row->count(); $i < $columns; $i++)
                    <td style="width: {{ number_format(100 / $columns, 2) }}%;"></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
