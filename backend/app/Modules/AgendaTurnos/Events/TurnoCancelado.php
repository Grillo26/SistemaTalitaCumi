<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Support\MotivoTurnoData;

/**
 * Cancelación individual o de un turno dentro de una cancelación masiva.
 */
final class TurnoCancelado
{
    /**
     * @param  list<string>  $advertencias
     */
    public function __construct(
        public readonly int $turnoId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly string $fecha,
        public readonly string $horaInicio,
        public readonly string $horaFin,
        public readonly MotivoTurnoData $motivo,
        public readonly bool $consumeSaldo,
        public readonly array $advertencias,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly bool $masivo = false,
        public readonly ?string $loteId = null,
        public readonly ?string $motivoTexto = null,
    ) {}
}
