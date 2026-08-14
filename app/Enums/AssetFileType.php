<?php

namespace App\Enums;

enum AssetFileType: string
{
    case Factura = 'factura';
    case Foto = 'foto';
    case Documento = 'documento';

    public function label(): string
    {
        return match ($this) {
            self::Factura => 'Factura',
            self::Foto => 'Fotografía',
            self::Documento => 'Documento',
        };
    }
}
