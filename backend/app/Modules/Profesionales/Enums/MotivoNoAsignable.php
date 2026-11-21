<?php

namespace App\Modules\Profesionales\Enums;

/**
 * Motivos por los que un profesional activo todavía no es asignable
 * (03/RN-03, 03/RN-07, 03/RN-09).
 */
enum MotivoNoAsignable: string
{
    case PROFESIONAL_INACTIVO = 'PROFESIONAL_INACTIVO';
    case SIN_ESPECIALIDAD_ACTIVA = 'SIN_ESPECIALIDAD_ACTIVA';
    case ESPECIALIDAD_INACTIVA = 'ESPECIALIDAD_INACTIVA';
    case SIN_SUCURSAL_ACTIVA = 'SIN_SUCURSAL_ACTIVA';
    case SUCURSAL_INACTIVA = 'SUCURSAL_INACTIVA';

    public function label(): string
    {
        return match ($this) {
            self::PROFESIONAL_INACTIVO => 'El profesional está inactivo.',
            self::SIN_ESPECIALIDAD_ACTIVA => 'No tiene una especialidad principal activa.',
            self::ESPECIALIDAD_INACTIVA => 'La especialidad está inactiva.',
            self::SIN_SUCURSAL_ACTIVA => 'No tiene una sucursal de trabajo activa.',
            self::SUCURSAL_INACTIVA => 'Su sucursal de trabajo está inactiva.',
        };
    }
}
