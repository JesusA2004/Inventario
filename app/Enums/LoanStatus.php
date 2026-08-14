<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Prestado = 'prestado';
    case Devuelto = 'devuelto';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Prestado => 'Prestado',
            self::Devuelto => 'Devuelto',
            self::Vencido => 'Vencido',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Prestado => 'blue',
            self::Devuelto => 'green',
            self::Vencido => 'red',
            self::Cancelado => 'slate',
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
