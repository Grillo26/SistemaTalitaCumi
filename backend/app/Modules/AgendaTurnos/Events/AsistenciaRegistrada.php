<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RF-001, 07.04/RF-002, 07.04/RF-011.
 */
final class AsistenciaRegistrada
{
    public function __construct(
        public readonly int $turnoId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly EstadoTurno $estadoAnterior,
        public readonly EstadoTurno $estadoNuevo,
        public readonly ?string $origenTipo,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly int $version,
        public readonly string $asistenciaRegistradaEn,
    ) {}
}
