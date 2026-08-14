<?php

namespace App\Services;

use App\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;

class AssetLabelPdfService
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    /**
     * Build a printable PDF sheet of QR labels ready for adhesive label paper.
     *
     * @param  Collection<int, Asset>  $assets
     */
    public function build(Collection $assets, string $template = 'standard'): \Barryvdh\DomPDF\PDF
    {
        $columns = $template === 'compact' ? 3 : 2;

        $labels = $assets->map(fn (Asset $asset) => [
            'type_name' => mb_strtoupper($asset->assetType->name),
            'internal_code' => $asset->internal_code,
            'serial_number' => $asset->serial_number,
            'company_name' => $asset->company->name,
            'qr' => $this->qrCodeService->dataUri($this->qrCodeService->publicUrl($asset), 240),
        ]);

        return Pdf::loadView('pdf.labels', [
            'labels' => $labels,
            'columns' => $columns,
        ])->setPaper('letter', 'portrait');
    }

    public function buildSingleLabel(Asset $asset): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.label-single', [
            'label' => [
                'type_name' => mb_strtoupper($asset->assetType->name),
                'internal_code' => $asset->internal_code,
                'serial_number' => $asset->serial_number,
                'company_name' => $asset->company->name,
                'qr' => $this->qrCodeService->dataUri($this->qrCodeService->publicUrl($asset), 400),
            ],
        ])->setPaper([0, 0, 226.77, 226.77], 'portrait');
    }
}
