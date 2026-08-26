<?php

namespace App\Services\Responsibilities;

use Carbon\CarbonInterface;

/**
 * Formatea fechas en español ("24 de marzo de 2026") sin depender del locale
 * del servidor (por defecto "en" salvo que se configure APP_LOCALE), para
 * que el documento siempre se vea igual sin importar dónde corra la app.
 * Compartido entre el snapshot de la responsiva (placeholder
 * {{ issued_date_long }}) y el pie de fecha del PDF.
 */
class SpanishDateFormatter
{
    /**
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public static function long(?CarbonInterface $date): string
    {
        if (! $date) {
            return '';
        }

        return sprintf('%d de %s de %d', $date->day, self::MONTHS[$date->month], $date->year);
    }
}
