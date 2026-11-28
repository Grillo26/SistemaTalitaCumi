<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RN-001, 07.04/RN-005, 07.04/RF-008.
 *
 * Transiciones de asistencia:
 * - programado → asistió/faltó (registro)
 * - asistió ↔ faltó (corrección)
 * - mismo estado = no-op idempotente
 * - cancelado/reprogramado = bloqueado
 * - nunca se vuelve a programado
 */
final class TransicionAsistenciaRule
{
    /**
     * @return list<string>
     */
    public function errores(
        EstadoTurno $actual,
        EstadoTurno $destino,
        bool $correccion,
    ): array {
        if (! in_array($destino, [EstadoTurno::ASISTIO, EstadoTurno::FALTO], true)) {
            return ['El estado de destino debe ser «Asistió» o «Faltó».'];
        }

        if ($actual === $destino) {
            return [];
        }

        if ($actual === EstadoTurno::CANCELADO) {
            return ['El turno está cancelado y no admite asistencia.'];
        }

        if ($actual === EstadoTurno::REPROGRAMADO) {
            return ['El turno fue reprogramado; gestioná el turno vigente.'];
        }

        if ($actual === EstadoTurno::PROGRAMADO) {
            return $correccion
                ? ['El turno todavía no tiene asistencia registrada; usá el registro inicial.']
                : [];
        }

        if (in_array($actual, [EstadoTurno::ASISTIO, EstadoTurno::FALTO], true)) {
            return $correccion
                ? []
                : ['El turno ya tiene asistencia registrada; usá la corrección.'];
        }

        return ['El turno no admite asistencia en su estado actual.'];
    }

    public function esNoOp(EstadoTurno $actual, EstadoTurno $destino): bool
    {
        return $actual === $destino
            && in_array($destino, [EstadoTurno::ASISTIO, EstadoTurno::FALTO], true);
    }
}
