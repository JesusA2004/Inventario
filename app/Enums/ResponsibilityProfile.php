<?php

namespace App\Enums;

/**
 * Perfil de responsiva configurable en el catálogo de Tipos de Activo
 * (AssetType::responsibility_profile). Determina el título y el "tipo de
 * documento" que se usa en la responsiva de equipo, evitando que el PDF
 * siempre diga "Equipo de cómputo" sin importar qué se entregue.
 */
enum ResponsibilityProfile: string
{
    case Computer = 'computer';
    case Mobile = 'mobile';
    case Display = 'display';
    case Network = 'network';
    case Peripheral = 'peripheral';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Computer => 'Equipo de cómputo',
            self::Mobile => 'Dispositivo móvil',
            self::Display => 'Equipo / accesorio de TI',
            self::Network => 'Equipo de red',
            self::Peripheral => 'Periférico',
            self::General => 'Equipo de TI',
        };
    }

    /** Título del documento tal como aparece en el encabezado del PDF. */
    public function documentTitle(): string
    {
        return match ($this) {
            self::Computer => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
            self::Mobile => 'RESPONSIVA DE DISPOSITIVO MÓVIL',
            self::Display => 'RESPONSIVA DE EQUIPO / ACCESORIO DE TI',
            self::Network, self::Peripheral, self::General => 'RESPONSIVA DE EQUIPO DE TI',
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
