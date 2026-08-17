<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\AssetLabelPdfService;
use App\Services\LabelSizeResolver;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
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

    public function show(Request $request, Asset $asset): Response
    {
        $data = $request->validate([
            'size' => ['nullable', 'string', 'in:small,medium,large,custom'],
            'width_mm' => ['required_if:size,custom', 'nullable', 'numeric', 'min:'.LabelSizeResolver::MIN_WIDTH_MM, 'max:'.LabelSizeResolver::MAX_WIDTH_MM],
            'height_mm' => ['required_if:size,custom', 'nullable', 'numeric', 'min:'.LabelSizeResolver::MIN_HEIGHT_MM, 'max:'.LabelSizeResolver::MAX_HEIGHT_MM],
        ]);

        $filename = $this->qrCodeService->fileBaseName($asset->internal_code, $asset->assetType->name).'-etiqueta.pdf';

        return $this->labelPdfService
            ->buildSingleLabel(
                $asset,
                $data['size'] ?? 'medium',
                isset($data['width_mm']) ? (float) $data['width_mm'] : null,
                isset($data['height_mm']) ? (float) $data['height_mm'] : null,
            )
            ->stream($filename);
    }
}
