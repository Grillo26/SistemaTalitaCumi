<?php

namespace App\Modules\AgendaTurnos\Enums;

/**
 * Origen de un motivo de cambio de turno (RF-003 / RN-006).
 *
 * Es metadata configurable: la decisión de consumir saldo se toma por el
 * flag `descuenta_saldo`, no por el origen.
 */
enum OrigenMotivoTurno: string
{
    case CENTRO = 'centro';
    case PACIENTE = 'paciente';

    public function label(): string
    {
        return match ($this) {
            self::CENTRO => 'Centro',
            self::PACIENTE => 'Paciente',
        };
    }
}
