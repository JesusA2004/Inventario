<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AssetLabelPdfService;
use App\Services\QrCodeService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\Response;

class AssetLabelController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AssetLabelPdfService $labelPdfService,
        private readonly QrCodeService $qrCodeService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-activos'),
        ];
    }

    public function show(Asset $asset): Response
    {
        $filename = $this->qrCodeService->fileBaseName($asset->internal_code, $asset->assetType->name).'-etiqueta.pdf';

        return $this->labelPdfService->buildSingleLabel($asset)->stream($filename);
    }
}
