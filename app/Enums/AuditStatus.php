<?php

namespace App\Enums;

enum AuditStatus: string
{
    case EnProgreso = 'en_progreso';
    case Finalizada = 'finalizada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::EnProgreso => 'En progreso',
            self::Finalizada => 'Finalizada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnProgreso => 'blue',
            self::Finalizada => 'green',
            self::Cancelada => 'slate',
        };
    }
}
