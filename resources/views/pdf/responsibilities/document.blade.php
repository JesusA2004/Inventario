<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $snapshot['document_title'] ?? 'Responsiva de equipo' }} — {{ $responsibility->document_number }}</title>
    <style>
        /* @page sin margen: el fondo (membrete completo o logo/watermark/pie
           por separado) cubre la hoja exacta sin desplazarse. dompdf posiciona
           los elementos "fixed" respecto a la caja de márgenes del @page, no
           respecto al borde real de la hoja -si aquí hubiera margen, el fondo
           quedaría corrido esa misma cantidad, que es justo el bug de
           membrete "desalineado" que tenía la versión anterior-. Los márgenes
           "de lectura" se recrean con padding en .page-content, así el fondo
           y el contenido quedan totalmente desacoplados. */
        @page { margin: 0; }

        /* Arial no viene embebido en dompdf (ni se puede generar sin
           licenciarla); "sans-serif" es lo que dompdf sí resuelve a
           Helvetica -tipográficamente casi idéntica a Arial en PDF-, así que
           queda como último eslabón de la pila en vez de forzar un font-family
           que dompdf no reconoce y que caería en el "serif" por defecto. */
        body { font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 11pt; line-height: 1.35; }

        .page-content { padding: 26mm 18mm 22mm 18mm; position: relative; z-index: 1; }

        /* Membrete de página completa (logo + fondo + franja ya compuestos en
           una sola imagen, como el oficial de MR LANA): cubre exactamente una
           hoja carta, repetido en cada página. */
        .letterhead { position: fixed; top: 0; left: 0; width: 8.5in; height: 11in; z-index: -1000; }
        .letterhead img { width: 8.5in; height: 11in; }

        /* Alternativa por capas para una empresa sin membrete pre-compuesto:
           logo arriba-izquierda (tamaño acotado, sin deformar: max-width +
           max-height con proporción intacta), marca de agua grande y sutil
           detrás del texto, franja de pie completa sin recortes. Todas
           "fixed" para repetirse en cada página. */
        .header-logo { position: fixed; top: 8mm; left: 12mm; z-index: -1000; }
        .header-logo img { max-width: 42mm; max-height: 20mm; }

        .watermark { position: fixed; top: 0; left: 0; width: 8.5in; height: 11in; z-index: -1000; text-align: center; }
        .watermark img { width: 75%; margin-top: 3in; opacity: 0.07; }

        .footer-strip { position: fixed; bottom: 0; left: 0; width: 8.5in; z-index: -1000; }
        .footer-strip img { width: 8.5in; display: block; }

        h1 { font-size: 13pt; text-align: center; color: #000; font-weight: bold; margin: 0 0 4mm; }
        .issued-line { text-align: right; font-size: 10.5pt; color: #000; margin-bottom: 6mm; }

        .recipient-block { margin: 0 0 6mm; font-size: 11pt; font-weight: bold; }
        .recipient-block p { margin: 0; }

        p.legal { text-align: justify; margin: 0 0 3mm; }

        /* Espacio en blanco al inicio de la página 2, antes de que arranque
           el texto: sin esto, el párrafo queda pegado justo arriba, contra
           el borde superior de lectura. */
        .page-break { page-break-before: always; height: 45mm; }

        table.characteristics { width: 100%; border-collapse: collapse; margin: 3mm 0 4mm; }
        table.characteristics th {
            background: #d9d9d9; color: #000; text-align: left; padding: 2mm; font-size: 10pt;
            font-weight: bold; border: 0.75pt solid #000;
        }
        table.characteristics td { padding: 1.5mm 2mm; font-size: 10.5pt; line-height: 1.15; border: 0.75pt solid #000; background: #fff; }

        .signatures { margin-top: 14mm; page-break-inside: avoid; }
        table.signatures-table { width: 100%; border-collapse: collapse; }
        /* vertical-align:bottom combinado con position:relative dentro de un
           <td> es inconsistente en dompdf (desalinea una columna respecto a
           la otra); ambas columnas alinean arriba con la misma estructura
           fija, así quedan siempre a la misma altura. */
        table.signatures-table td { width: 50%; text-align: center; vertical-align: top; padding: 0 6mm; }
        .signature-label { font-size: 11pt; font-weight: bold; color: #000; text-transform: uppercase; margin-bottom: 2mm; }
        .signature-space { height: 20mm; }
        .signature-image { display: block; margin: 0 auto; max-height: 18mm; }
        .signature-line { border-top: 0.75pt solid #000; padding-top: 1.5mm; }
        .signature-name { font-weight: bold; font-size: 10.5pt; }
        .signature-position { font-size: 10pt; text-transform: uppercase; }

        .signed-meta {
            margin-top: 4mm; font-size: 7.5pt; color: #555; text-align: center;
            border-top: 0.5pt dashed #999; padding-top: 2mm;
        }

        /* Pie con número de documento y "Página X de Y" en cada hoja. El
           número de página actual sí es fiable con el contador nativo de
           dompdf (counter(page)); el TOTAL de páginas (counter(pages)) se
           calculó aparte con una pasada previa de render (ver
           AssetResponsibilityPdfService) porque dompdf lo reporta en 0 en
           documentos de una sola página -el "Página 1 de 0" que se
           corrigió-, así que aquí siempre se imprime como texto plano. */
        .doc-footer {
            position: fixed; bottom: 8mm; left: 0; right: 0;
            font-size: 7.5pt; color: #888; text-align: center;
        }
        .current-page:before { content: counter(page); }
    </style>
</head>
<body>
    @if($letterheadPath)
        <div class="letterhead"><img src="{{ $letterheadPath }}" alt=""></div>
    @else
        @if($headerLogoPath)
            <div class="header-logo"><img src="{{ $headerLogoPath }}" alt=""></div>
        @endif
        @if($watermarkPath)
            <div class="watermark"><img src="{{ $watermarkPath }}" alt=""></div>
        @endif
        @if($footerPath)
            <div class="footer-strip"><img src="{{ $footerPath }}" alt=""></div>
        @endif
    @endif

    <div class="page-content">
        <h1>{{ $snapshot['document_title'] ?? 'RESPONSIVA DE EQUIPO' }}</h1>
        <div class="issued-line">{{ $responsibility->city ? $responsibility->city.', a ' : '' }}{{ $issuedAtLabel }}.</div>

        @if(filled($snapshot['recipient_block'] ?? null))
            <div class="recipient-block">
                @foreach(explode("\n", trim($snapshot['recipient_block'])) as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
        @endif

        @foreach(explode("\n\n", trim($responsibility->legal_text_snapshot)) as $paragraph)
            @php($paragraph = trim($paragraph))
            @if($paragraph === \App\Models\ResponsibilityTemplate::CHARACTERISTICS_TABLE_TOKEN)
                <table class="characteristics">
                    <thead>
                        <tr>
                            <th style="width: 38%;">{{ $snapshot['characteristics_name_header'] ?? 'Nombre de la característica' }}</th>
                            <th>{{ $snapshot['characteristics_value_header'] ?? 'Descripción detallada' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($snapshot['characteristics'] ?? []) as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ $row['value'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif($paragraph === \App\Models\ResponsibilityTemplate::PAGE_BREAK_TOKEN)
                <div class="page-break"></div>
            @else
                <p class="legal">{!! nl2br(e($paragraph)) !!}</p>
            @endif
        @endforeach

        <div class="signatures">
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="signature-label">{{ $snapshot['receiver_label'] ?? \App\Models\ResponsibilityTemplate::DEFAULT_RECEIVER_LABEL }}</div>
                        <div class="signature-space">
                            @if($signed && $signatureAbsolutePath && is_file($signatureAbsolutePath))
                                <img class="signature-image" src="{{ $signatureAbsolutePath }}" alt="Firma electrónica">
                            @endif
                        </div>
                        <div class="signature-line">
                            <div class="signature-name">{{ $responsibility->receiver_name }}</div>
                            <div class="signature-position">{{ $responsibility->receiver_position }}</div>
                        </div>
                    </td>
                    <td>
                        {{-- Sin nombre ni puesto impreso a propósito: la
                             entrega la puede firmar cualquier apoderado legal
                             de la empresa, no una persona específica. --}}
                        <div class="signature-label">{{ $snapshot['grantor_label'] ?? \App\Models\ResponsibilityTemplate::DEFAULT_GRANTOR_LABEL }}</div>
                        <div class="signature-space"></div>
                        <div class="signature-line">&nbsp;</div>
                    </td>
                </tr>
            </table>

            @if($signed)
                <div class="signed-meta">
                    Firmado electrónicamente el: {{ $signedAtLabel }} &nbsp;·&nbsp;
                    ID de documento: {{ $responsibility->document_number }} &nbsp;·&nbsp;
                    Código de verificación: {{ $verificationCode }}
                </div>
            @endif
        </div>
    </div>

    <div class="doc-footer">
        {{ $responsibility->document_number }}
        {{-- El contador de página CSS (counter(page)/counter(pages)) solo
             existe en el motor de paginación de dompdf; en una vista previa
             normal de navegador no hay "páginas", así que ese bloque se
             omite ahí en vez de mostrar un conteo sin sentido. --}}
        @unless($isWebPreview ?? false)
            &nbsp;·&nbsp; Página <span class="current-page"></span> de {{ $totalPages }}
        @endunless
    </div>
</body>
</html>
