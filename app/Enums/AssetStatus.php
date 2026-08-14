<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Activo = 'activo';
    case Almacenado = 'almacenado';
    case Danado = 'danado';
    case Baja = 'baja';
    case EnRevision = 'en_revision';
    case SinEstatus = 'sin_estatus';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Almacenado => 'Almacenado',
            self::Danado => 'Dañado',
            self::Baja => 'Baja',
            self::EnRevision => 'En revisión',
            self::SinEstatus => 'Sin estatus',
        };
    }

    /** Badge color token consumed by the Vue AssetStatusBadge component. */
    public function color(): string
    {
        return match ($this) {
            self::Activo => 'green',
            self::Almacenado => 'blue',
            self::Danado => 'red',
            self::Baja => 'slate',
            self::EnRevision => 'amber',
            self::SinEstatus => 'gray',
        };
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
