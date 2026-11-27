<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * [Reprogramación y Cancelación] RT-001 / control optimista.
 *
 * El cliente envía el estado que vio; si el turno cambió bajo lock, se
 * rechaza con 409 y se devuelve el estado actual.
 */
final class EstadoEsperadoRule
{
    public function coincide(EstadoTurno $actual, EstadoTurno $esperado): bool
    {
        return $actual === $esperado;
    }

    public function mensaje(EstadoTurno $actual): string
    {
        return "El turno cambió de estado a «{$actual->label()}». Recargá la ficha antes de continuar.";
    }
}
