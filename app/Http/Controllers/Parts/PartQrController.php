<?php

namespace App\Http\Controllers\Parts;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class PartQrController extends Controller implements HasMiddleware
{
    public function __construct(private readonly QrCodeService $qrCodeService) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:ver-piezas'),
        ];
    }

    public function show(Part $part): Response
    {
        $result = $this->qrCodeService->png($this->qrCodeService->partPublicUrl($part), 500);

        return response($result->getString(), 200)->header('Content-Type', $result->getMimeType());
    }

    public function download(Part $part): Response
    {
        $result = $this->qrCodeService->png($this->qrCodeService->partPublicUrl($part), 800);
        $filename = Str::slug($part->internal_code).'-qr.png';

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
