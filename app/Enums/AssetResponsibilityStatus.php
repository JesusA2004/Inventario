<?php

namespace App\Enums;

enum AssetResponsibilityStatus: string
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case Vista = 'vista';
    case FirmadaDigitalmente = 'firmada_digitalmente';
    case FirmaFisicaCargada = 'firma_fisica_cargada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::Vista => 'Vista',
            self::FirmadaDigitalmente => 'Firmada digitalmente',
            self::FirmaFisicaCargada => 'Firma física cargada',
            self::Cancelada => 'Cancelada',
        };
    }

    /** Badge color token consumido por el frontend, igual que AssetStatus::color(). */
    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'slate',
            self::Enviada => 'blue',
            self::Vista => 'amber',
            self::FirmadaDigitalmente => 'green',
            self::FirmaFisicaCargada => 'green',
            self::Cancelada => 'red',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::FirmadaDigitalmente, self::FirmaFisicaCargada, self::Cancelada], true);
    }

    /**
     * @return array<int, array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label(), 'color' => $case->color()],
            self::cases(),
        );
    }
}
