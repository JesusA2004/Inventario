<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AssetQrController extends Controller implements HasMiddleware
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-activos'),
        ];
    }

    public function show(Asset $asset): Response
    {
        $result = $this->qrCodeService->png($this->qrCodeService->publicUrl($asset), 500);

        return response($result->getString(), 200)->header('Content-Type', $result->getMimeType());
    }

    public function download(Asset $asset): Response
    {
        $result = $this->qrCodeService->png($this->qrCodeService->publicUrl($asset), 800);
        $filename = $this->qrCodeService->fileBaseName($asset->internal_code, $asset->assetType->name).'-qr.png';

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
