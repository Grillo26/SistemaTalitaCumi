<?php

namespace App\Modules\AgendaTurnos\Events;

/**
 * 07.05/RF-002 / 07.05/RF-009.
 */
final class TurnoDiagnosticoProgramado
{
    public function __construct(
        public readonly int $turnoId,
        public readonly int $diagnosticoId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly int $profesionalId,
        public readonly string $fecha,
        public readonly string $horaInicio,
        public readonly string $horaFin,
        public readonly int $duracionMinutos,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
