<?php

namespace App\Modules\AgendaTurnos\Enums;

/**
 * Evento registrado en la bitácora de asistencia.
 *
 * La acción de bitácora sigue siendo ASISTIO/FALTO para que el historial de
 * 07.03 lo muestre sin cambios; este enum distingue el tipo de operación.
 */
enum AsistenciaEvento: string
{
    case REGISTRO = 'registro';
    case CORRECCION = 'correccion';
    case CORRECCION_FUERA_VENTANA = 'correccion_fuera_ventana';

    public function label(): string
    {
        return match ($this) {
            self::REGISTRO => 'Registro de asistencia',
            self::CORRECCION => 'Corrección dentro de ventana',
            self::CORRECCION_FUERA_VENTANA => 'Corrección fuera de ventana',
        };
    }

    public function esCorreccion(): bool
    {
        return $this === self::CORRECCION || $this === self::CORRECCION_FUERA_VENTANA;
    }
}
