<?php

namespace App\Enums;

enum MovementType: string
{
    case Alta = 'alta';
    case Asignacion = 'asignacion';
    case CambioResponsable = 'cambio_responsable';
    case CambioSucursal = 'cambio_sucursal';
    case CambioArea = 'cambio_area';
    case CambioEstado = 'cambio_estado';
    case Prestamo = 'prestamo';
    case Devolucion = 'devolucion';
    case Revision = 'revision';
    case Baja = 'baja';
    case Reactivacion = 'reactivacion';
    case Edicion = 'edicion';

    public function label(): string
    {
        return match ($this) {
            self::Alta => 'Alta de activo',
            self::Asignacion => 'Asignación',
            self::CambioResponsable => 'Cambio de responsable',
            self::CambioSucursal => 'Cambio de sucursal',
            self::CambioArea => 'Cambio de área',
            self::CambioEstado => 'Cambio de estado',
            self::Prestamo => 'Préstamo',
            self::Devolucion => 'Devolución',
            self::Revision => 'Revisión',
            self::Baja => 'Baja',
            self::Reactivacion => 'Reactivación',
            self::Edicion => 'Edición',
        };
    }
}
