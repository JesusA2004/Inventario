<?php

namespace App\Enums;

enum AuditItemStatus: string
{
    case Pendiente = 'pendiente';
    case Encontrado = 'encontrado';
    case NoEncontrado = 'no_encontrado';
    case UbicacionIncorrecta = 'ubicacion_incorrecta';
    case ResponsableIncorrecto = 'responsable_incorrecto';
    case RequiereRevision = 'requiere_revision';
    case Danado = 'danado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Encontrado => 'Encontrado',
            self::NoEncontrado => 'No encontrado',
            self::UbicacionIncorrecta => 'Ubicación incorrecta',
            self::ResponsableIncorrecto => 'Responsable incorrecto',
            self::RequiereRevision => 'Requiere revisión',
            self::Danado => 'Dañado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Encontrado => 'green',
            self::NoEncontrado => 'red',
            self::UbicacionIncorrecta => 'amber',
            self::ResponsableIncorrecto => 'amber',
            self::RequiereRevision => 'amber',
            self::Danado => 'red',
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
