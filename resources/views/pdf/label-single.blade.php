<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiqueta</title>
    <style>
        @page { margin: 0; }
        body { font-family: "DejaVu Sans", sans-serif; color: #0f172a; margin: 0; }
        .label {
            width: {{ $dimensions['width'] }}mm;
            margin: {{ $marginMm }}mm;
            text-align: center;
            line-height: 1;
        }
        .type { font-size: {{ $dimensions['font_type'] }}pt; font-weight: bold; letter-spacing: 0.5pt; text-transform: uppercase; }
        .code { font-size: {{ $dimensions['font_code'] }}pt; font-weight: bold; margin: 1mm 0; }
        .qr { width: {{ $dimensions['qr'] }}mm; height: {{ $dimensions['qr'] }}mm; margin: 0.5mm auto; }
        .serial { font-size: {{ $dimensions['font_small'] }}pt; margin-top: 0.5mm; }
        .company { font-size: {{ $dimensions['font_small'] }}pt; font-weight: bold; color: #1e3a5f; margin-top: 0.5mm; }
    </style>
</head>
<body>
    <div class="label">
        <div class="type">{{ $label['type_name'] }}</div>
        <div class="code">{{ $label['internal_code'] }}</div>
        <img class="qr" src="{{ $label['qr'] }}" alt="QR">
        @if ($label['serial_number'])
            <div class="serial">S/N: {{ $label['serial_number'] }}</div>
        @endif
        <div class="company">{{ $label['company_name'] }}</div>
    </div>
</body>
</html>
