<?php

namespace App\Services\Responsibilities;

use App\Models\AssetResponsibility;
use App\Models\CompanyDocumentSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Único punto de generación del PDF de responsiva (borrador/sin firmar y
 * firmado). Nunca se mete la lógica de armado en un controller. El PDF
 * firmado se genera UNA sola vez y queda congelado en signed_pdf_path: no se
 * vuelve a renderizar en cada descarga.
 */
class AssetResponsibilityPdfService
{
    private const DISK = 'local';

    /**
     * PDF para imprimir/revisar mientras la responsiva no está firmada.
     * Se cachea en disco y solo se regenera si se invalidó (p. ej. al editar
     * características en borrador); ver AssetResponsibilityService::updateCharacteristics().
     */
    public function unsigned(AssetResponsibility $responsibility): string
    {
        if ($responsibility->unsigned_pdf_path && Storage::disk(self::DISK)->exists($responsibility->unsigned_pdf_path)) {
            return $responsibility->unsigned_pdf_path;
        }

        $pdf = $this->render($responsibility);
        $path = "responsibilities/{$responsibility->id}/borrador-{$responsibility->public_id}.pdf";
        Storage::disk(self::DISK)->put($path, $pdf->output());
        $responsibility->update(['unsigned_pdf_path' => $path]);

        return $path;
    }

    /**
     * Genera el PDF firmado congelado, embebiendo la firma electrónica del
     * receptor. Debe llamarse una sola vez, inmediatamente después de
     * guardar la firma (signature_path/signature_sha256 ya deben existir).
     */
    public function signed(AssetResponsibility $responsibility): string
    {
        $signatureAbsolutePath = $responsibility->signature_path
            ? Storage::disk(self::DISK)->path($responsibility->signature_path)
            : null;

        $pdf = $this->render($responsibility, signatureAbsolutePath: $signatureAbsolutePath, signed: true);
        $bytes = $pdf->output();

        $path = "responsibilities/{$responsibility->id}/firmada-{$responsibility->public_id}.pdf";
        Storage::disk(self::DISK)->put($path, $bytes);

        $responsibility->update([
            'signed_pdf_path' => $path,
            'document_sha256' => hash('sha256', $bytes),
        ]);

        return $path;
    }

    private function render(
        AssetResponsibility $responsibility,
        ?string $signatureAbsolutePath = null,
        bool $signed = false,
    ): \Barryvdh\DomPDF\PDF {
        $data = $this->viewData($responsibility, $signatureAbsolutePath, $signed);

        // dompdf reporta counter(pages) como 0 en documentos de una sola
        // página ("Página 1 de 0"). En vez de depender de ese contador CSS
        // para el total, se hace una primera pasada muda solo para conocer
        // cuántas páginas resultan, y se inyecta ese número como texto plano
        // en el render final; el número de página ACTUAL sigue usando el
        // contador nativo de dompdf (ese sí es confiable página por página).
        $probe = Pdf::loadView('pdf.responsibilities.document', [...$data, 'totalPages' => 1])
            ->setPaper('letter', 'portrait');
        $probe->render();
        $totalPages = $probe->getDomPDF()->getCanvas()->get_page_count();

        return Pdf::loadView('pdf.responsibilities.document', [...$data, 'totalPages' => $totalPages])
            ->setPaper('letter', 'portrait');
    }

    /**
     * Datos para renderizar pdf.responsibilities.document, compartidos entre
     * el PDF y la vista previa en pantalla (AssetResponsibilityController::preview()):
     * una sola fuente de verdad para que la preview se vea igual al PDF
     * final, en vez de mantener dos plantillas por separado.
     *
     * $forWeb=true resuelve las imágenes de branding como URLs públicas (para
     * la vista previa en el navegador) en vez de rutas de archivo absolutas
     * (lo que dompdf necesita para incrustarlas en el PDF).
     *
     * @return array<string, mixed>
     */
    public function viewData(
        AssetResponsibility $responsibility,
        ?string $signatureAbsolutePath = null,
        bool $signed = false,
        bool $forWeb = false,
    ): array {
        return [
            'responsibility' => $responsibility,
            'snapshot' => $responsibility->asset_snapshot ?? [],
            ...$this->resolveBranding($responsibility, $forWeb),
            'signed' => $signed,
            'signatureAbsolutePath' => $signatureAbsolutePath,
            'issuedAtLabel' => SpanishDateFormatter::long($responsibility->issued_at),
            'signedAtLabel' => $responsibility->signed_at?->format('d/m/Y H:i'),
            'verificationCode' => $responsibility->verificationCode(),
        ];
    }

    /**
     * Resuelve las imágenes de branding de la responsiva, con dos
     * estrategias posibles por empresa (ver
     * company_document_settings.letterhead_path/header_logo_path/
     * watermark_path/footer_path):
     *  - Membrete de página completa ya compuesto (logo + fondo + franja en
     *    una sola imagen), como el oficial de MR LANA. Tiene prioridad.
     *  - Piezas sueltas (logo arriba-izquierda / watermark / franja de pie),
     *    para una empresa sin un membrete pre-compuesto.
     * Si la empresa no configuró nada, cae al membrete oficial de MR LANA
     * (compatibilidad con lo que ya existía) solo cuando el archivo está
     * presente; si no, el documento se genera sin membrete.
     *
     * @return array{letterheadPath: ?string, headerLogoPath: ?string, watermarkPath: ?string, footerPath: ?string}
     */
    private function resolveBranding(AssetResponsibility $responsibility, bool $forWeb = false): array
    {
        $company = $responsibility->company;
        $settings = $company ? CompanyDocumentSetting::forCompany($company) : null;

        $letterhead = $this->publicDiskPath($settings?->letterhead_path, $forWeb)
            ?? $this->defaultMrLanaLetterhead($forWeb);

        if ($letterhead) {
            return ['letterheadPath' => $letterhead, 'headerLogoPath' => null, 'watermarkPath' => null, 'footerPath' => null];
        }

        return [
            'letterheadPath' => null,
            'headerLogoPath' => $this->publicDiskPath($settings?->header_logo_path, $forWeb),
            'watermarkPath' => $this->publicDiskPath($settings?->watermark_path, $forWeb),
            'footerPath' => $this->publicDiskPath($settings?->footer_path, $forWeb),
        ];
    }

    private function publicDiskPath(?string $path, bool $forWeb): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $forWeb ? Storage::disk('public')->url($path) : Storage::disk('public')->path($path);
    }

    /**
     * Membrete oficial de MR LANA colocado manualmente en
     * public/branding/responsivas/mr-lana/ (no en el disco "public" de
     * storage): es el valor por defecto histórico, antes de que existiera
     * configuración de branding por empresa. Se probaron varias extensiones
     * porque el archivo real se entregó como .jpeg, no .png.
     */
    private function defaultMrLanaLetterhead(bool $forWeb): ?string
    {
        foreach (['png', 'jpeg', 'jpg', 'webp'] as $extension) {
            $path = public_path("branding/responsivas/mr-lana/membrete-responsiva.{$extension}");

            if (is_file($path)) {
                return $forWeb ? asset("branding/responsivas/mr-lana/membrete-responsiva.{$extension}") : $path;
            }
        }

        return null;
    }
}
