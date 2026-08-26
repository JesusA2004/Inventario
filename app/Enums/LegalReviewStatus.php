<?php

namespace App\Enums;

enum LegalReviewStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Requiere validación jurídica',
            self::Approved => 'Aprobada',
        };
    }
}
