<?php

namespace App\Enums;

enum PartStatus: string
{
    case Funcional = 'funcional';
    case Danado = 'danado';
    case ParaRevision = 'para_revision';
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::Funcional => 'Funcional',
            self::Danado => 'Dañado',
            self::ParaRevision => 'Para revisión',
            self::Baja => 'Baja',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Funcional => 'green',
            self::Danado => 'red',
            self::ParaRevision => 'amber',
            self::Baja => 'slate',
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
