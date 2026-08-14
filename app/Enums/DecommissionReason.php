<?php

namespace App\Enums;

enum DecommissionReason: string
{
    case Danado = 'danado';
    case Robo = 'robo';
    case Extravio = 'extravio';
    case Vendido = 'vendido';
    case Reemplazado = 'reemplazado';
    case Donado = 'donado';
    case Obsoleto = 'obsoleto';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Danado => 'Dañado',
            self::Robo => 'Robo',
            self::Extravio => 'Extravío',
            self::Vendido => 'Vendido',
            self::Reemplazado => 'Reemplazado',
            self::Donado => 'Donado',
            self::Obsoleto => 'Obsoleto',
            self::Otro => 'Otro',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
