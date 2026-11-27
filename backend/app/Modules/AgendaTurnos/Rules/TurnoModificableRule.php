<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * [Reprogramación y Cancelación] RN-003 / RF-007.
 *
 * Solo un turno Programado puede reprogramarse o cancelarse. Asistió, Faltó,
 * Cancelado y Reprogramado se rechazan con un mensaje explícito.
 */
final class TurnoModificableRule
{
    public function permite(EstadoTurno $estado): bool
    {
        return $estado === EstadoTurno::PROGRAMADO;
    }

    public function mensaje(EstadoTurno $estado, string $accion): ?string
    {
        if ($this->permite($estado)) {
            return null;
        }

        return match ($estado) {
            EstadoTurno::ASISTIO, EstadoTurno::FALTO => $accion === 'cancelar'
                ? 'No se puede cancelar un turno ya registrado.'
                : 'No se puede reprogramar un turno ya registrado.',
            EstadoTurno::CANCELADO => 'El turno ya está cancelado.',
            EstadoTurno::REPROGRAMADO => 'El turno ya fue reprogramado. Gestioná el turno vigente.',
            default => 'El turno no admite cambios en su estado actual.',
        };
    }
}
