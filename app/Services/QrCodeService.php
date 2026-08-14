<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Part;
use App\Models\SystemSetting;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * The permanent public URL encoded in the QR. It never changes even if
     * the asset's internal code, brand, responsible, etc. are edited later.
     */
    public function publicUrl(Asset $asset): string
    {
        return $this->buildUrl('a', $asset->public_id);
    }

    public function partPublicUrl(Part $part): string
    {
        return $this->buildUrl('p', $part->public_id);
    }

    /**
     * Builds the permanent public URL. Uses the configurable QR base domain
     * from system settings when set, otherwise falls back to APP_URL.
     */
    private function buildUrl(string $segment, string $publicId): string
    {
        $base = SystemSetting::current()->qr_base_url;

        if ($base) {
            return rtrim($base, '/')."/{$segment}/{$publicId}";
        }

        return $segment === 'a'
            ? route('public.assets.show', $publicId)
            : route('public.parts.show', $publicId);
    }

    public function png(string $url, int $size = 400): ResultInterface
    {
        return (new PngWriter)->write(
            new QrCode(
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::Quartile,
                size: $size,
                margin: 8,
                foregroundColor: new Color(15, 23, 42),
                backgroundColor: new Color(255, 255, 255),
            )
        );
    }

    public function svg(string $url, int $size = 400): ResultInterface
    {
        return (new SvgWriter)->write(
            new QrCode(
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::Quartile,
                size: $size,
                margin: 8,
                foregroundColor: new Color(15, 23, 42),
                backgroundColor: new Color(255, 255, 255),
            )
        );
    }

    public function dataUri(string $url, int $size = 260): string
    {
        return $this->png($url, $size)->getDataUri();
    }

    /**
     * Filesystem-safe base name for downloads, e.g. "CML-EC-007-equipo-computo".
     */
    public function fileBaseName(string $internalCode, string $typeName): string
    {
        $slug = Str::slug($typeName);

        return Str::slug($internalCode).'-'.$slug;
    }
}
