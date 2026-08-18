<?php

namespace App\Services;

/**
 * Resolves the physical dimensions (in mm) for a QR label given a size
 * preset (or a custom size) plus the number of columns of the sheet it will
 * be printed on. Shared by the label PDF service and the label center
 * controller so the same rules that build the preview on the frontend are
 * enforced again on the server.
 */
class LabelSizeResolver
{
    /**
     * @var array<string, array{label: string, hint: string, width: float, height: float, qr: float, font_type: float, font_name: float, font_code: float, font_small: float}>
     */
    public const array PRESETS = [
        'small' => [
            'label' => 'Pequeño',
            'hint' => 'Mouse, cargador, accesorios',
            'width' => 32.0, 'height' => 34.0, 'qr' => 16.0,
            'font_type' => 6.0, 'font_name' => 7.0, 'font_code' => 8.5, 'font_small' => 5.5,
        ],
        'medium' => [
            'label' => 'Mediano',
            'hint' => 'Teclado, impresora, equipo mediano',
            'width' => 48.0, 'height' => 46.0, 'qr' => 23.0,
            'font_type' => 7.5, 'font_name' => 9.0, 'font_code' => 11.0, 'font_small' => 6.5,
        ],
        'large' => [
            'label' => 'Grande',
            'hint' => 'Laptop, monitor, CPU, equipo grande',
            'width' => 80.0, 'height' => 68.0, 'qr' => 38.0,
            'font_type' => 9.5, 'font_name' => 12.0, 'font_code' => 15.0, 'font_small' => 8.0,
        ],
    ];

    public const float MIN_WIDTH_MM = 25.0;

    public const float MAX_WIDTH_MM = 90.0;

    public const float MIN_HEIGHT_MM = 20.0;

    public const float MAX_HEIGHT_MM = 70.0;

    /**
     * Usable width per column on a US-letter sheet (10mm/6mm page margins,
     * 1mm cell padding on each side and 1mm label padding on each side),
     * rounded down for a small safety margin. Anything wider than this for
     * a given column count would get clipped or crowd the QR into
     * unreadable territory. Small labels have little content, so they can
     * go up to 5 per row instead of wasting the rest of the sheet.
     *
     * @var array<int, float>
     */
    public const array MAX_WIDTH_BY_COLUMNS = [
        2 => 92.0,
        3 => 60.0,
        4 => 45.0,
        5 => 34.0,
    ];

    /**
     * @return array{width: float, height: float, qr: float, font_type: float, font_code: float, font_small: float}
     */
    public function resolve(string $size, int $columns, ?float $customWidth = null, ?float $customHeight = null): array
    {
        if ($size === 'custom') {
            $width = max(self::MIN_WIDTH_MM, min(self::MAX_WIDTH_MM, $customWidth ?? self::MIN_WIDTH_MM));
            $height = max(self::MIN_HEIGHT_MM, min(self::MAX_HEIGHT_MM, $customHeight ?? self::MIN_HEIGHT_MM));
            $qr = $this->estimateQr($width, $height);

            // El texto se escala con la dimensión más chica, no solo el
            // ancho: una etiqueta ancha pero baja (p. ej. 90x20mm) igual
            // necesita letra pequeña para no desbordar verticalmente.
            $minSide = min($width, $height);

            $dimensions = [
                'width' => $width,
                'height' => $height,
                'qr' => $qr,
                'font_type' => $this->scaledFont($minSide, 0.19, 6.0, 11.0),
                'font_name' => $this->scaledFont($minSide, 0.22, 6.5, 13.0),
                'font_code' => $this->scaledFont($minSide, 0.27, 8.0, 16.0),
                'font_small' => $this->scaledFont($minSide, 0.15, 5.5, 9.0),
            ];
        } else {
            $preset = self::PRESETS[$size] ?? self::PRESETS['medium'];
            $dimensions = [
                'width' => $preset['width'],
                'height' => $preset['height'],
                'qr' => $preset['qr'],
                'font_type' => $preset['font_type'],
                'font_name' => $preset['font_name'],
                'font_code' => $preset['font_code'],
                'font_small' => $preset['font_small'],
            ];
        }

        return $dimensions;
    }

    public function fitsColumns(float $width, int $columns): bool
    {
        return $width <= (self::MAX_WIDTH_BY_COLUMNS[$columns] ?? self::MAX_WIDTH_BY_COLUMNS[2]);
    }

    public function maxWidthForColumns(int $columns): float
    {
        return self::MAX_WIDTH_BY_COLUMNS[$columns] ?? self::MAX_WIDTH_BY_COLUMNS[2];
    }

    /**
     * Payload shared with the frontend (label center and the asset list's QR
     * selection mode) so the size dialog's preview and validation stay in
     * sync with the server without duplicating the numbers by hand.
     *
     * @return array{presets: array, bounds: array{minWidth: float, maxWidth: float, minHeight: float, maxHeight: float}, maxWidthByColumns: array<int, float>}
     */
    public static function sharedProps(): array
    {
        return [
            'presets' => self::PRESETS,
            'bounds' => [
                'minWidth' => self::MIN_WIDTH_MM,
                'maxWidth' => self::MAX_WIDTH_MM,
                'minHeight' => self::MIN_HEIGHT_MM,
                'maxHeight' => self::MAX_HEIGHT_MM,
            ],
            'maxWidthByColumns' => self::MAX_WIDTH_BY_COLUMNS,
        ];
    }

    private function estimateQr(float $width, float $height): float
    {
        $raw = min($width, $height) * 0.55;

        return round(max(14.0, min($raw, min($width, $height) - 10)), 1);
    }

    private function scaledFont(float $width, float $ratio, float $min, float $max): float
    {
        return round(max($min, min($max, $width * $ratio)), 1);
    }
}
