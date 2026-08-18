<?php

namespace App\Services;

use App\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;

class AssetLabelPdfService
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
        private readonly LabelSizeResolver $sizeResolver,
    ) {}

    /**
     * Build a printable PDF sheet of QR labels ready for adhesive label paper.
     *
     * @param  Collection<int, Asset>  $assets
     */
    public function build(
        Collection $assets,
        int $columns = 2,
        string $size = 'medium',
        ?float $customWidth = null,
        ?float $customHeight = null,
    ): \Barryvdh\DomPDF\PDF {
        $dimensions = $this->sizeResolver->resolve($size, $columns, $customWidth, $customHeight);

        // El QR se renderiza a una resolución fija generosa (px) independiente
        // de su tamaño físico en mm: dompdf lo reescala, así se ve nítido
        // tanto en la etiqueta pequeña como en la grande.
        $labels = $assets->map(fn (Asset $asset) => [
            'type_name' => mb_strtoupper($asset->assetType->name),
            'name' => $asset->name,
            'internal_code' => $asset->internal_code,
            'serial_number' => $asset->serial_number,
            'company_name' => $asset->company->name,
            'qr' => $this->qrCodeService->dataUri($this->qrCodeService->publicUrl($asset), 320),
        ]);

        return Pdf::loadView('pdf.labels', [
            'labels' => $labels,
            'columns' => $columns,
            'dimensions' => $dimensions,
        ])->setPaper('letter', 'portrait');
    }

    /**
     * PDF de una sola etiqueta (para pegar en una calcomanía/sticker
     * individual), con página del tamaño físico exacto de la etiqueta: sin
     * esto, un tamaño de página fijo que no escala con el contenido es lo
     * que hacía que la etiqueta se cortara en dos hojas.
     */
    public function buildSingleLabel(Asset $asset, string $size = 'medium', ?float $customWidth = null, ?float $customHeight = null): \Barryvdh\DomPDF\PDF
    {
        $dimensions = $this->sizeResolver->resolve($size, 2, $customWidth, $customHeight);

        // mm → pt (1mm = 2.8346pt), con un margen fijo alrededor del
        // contenido para que nunca quede pegado al borde de corte. El alto
        // real del contenido (tipo + clave + QR + serie + empresa, con su
        // interlineado) casi siempre supera el "height" nominal del preset
        // -pensado como altura mínima de fila en la hoja por lotes, no como
        // límite exacto-, así que aquí se usa solo como referencia y se le
        // suma un colchón fijo para que nunca fuerce una segunda página.
        $marginMm = 4.0;
        $heightBufferMm = 16.0;
        $pageWidthPt = ($dimensions['width'] + $marginMm * 2) * 2.8346;
        $pageHeightPt = ($dimensions['height'] + $marginMm * 2 + $heightBufferMm) * 2.8346;

        return Pdf::loadView('pdf.label-single', [
            'label' => [
                'type_name' => mb_strtoupper($asset->assetType->name),
                'name' => $asset->name,
                'internal_code' => $asset->internal_code,
                'serial_number' => $asset->serial_number,
                'company_name' => $asset->company->name,
                'qr' => $this->qrCodeService->dataUri($this->qrCodeService->publicUrl($asset), 400),
            ],
            'dimensions' => $dimensions,
            'marginMm' => $marginMm,
        ])->setPaper([0, 0, $pageWidthPt, $pageHeightPt], 'portrait');
    }
}
