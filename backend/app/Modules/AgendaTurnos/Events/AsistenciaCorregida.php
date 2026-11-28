<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\AsistenciaEvento;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * 07.04/RF-004, 07.04/RF-005, 07.04/RF-006, 07.04/RF-013.
 */
final class AsistenciaCorregida
{
    public function __construct(
        public readonly int $turnoId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly EstadoTurno $estadoAnterior,
        public readonly EstadoTurno $estadoNuevo,
        public readonly ?string $origenTipo,
        public readonly AsistenciaEvento $evento,
        public readonly bool $fueraVentana,
        public readonly ?string $motivo,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly int $version,
        public readonly string $asistenciaRegistradaEn,
    ) {}
}
