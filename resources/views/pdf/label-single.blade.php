<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiqueta</title>
    <style>
        @page { margin: 4mm; }
        body { font-family: "DejaVu Sans", sans-serif; color: #0f172a; }
        .label { text-align: center; padding: 2mm; }
        .type { font-size: 10pt; font-weight: bold; letter-spacing: 0.5pt; text-transform: uppercase; }
        .code { font-size: 15pt; font-weight: bold; margin: 2mm 0; }
        .qr { width: 45mm; height: 45mm; margin: 1mm auto; }
        .serial { font-size: 8pt; margin-top: 1mm; }
        .company { font-size: 9pt; font-weight: bold; color: #1e3a5f; margin-top: 1mm; }
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
