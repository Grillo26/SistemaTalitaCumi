<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

/**
 * Un turno fue cancelado por una regeneración (no es la cancelación
 * individual de 7.3). Lo consumirán Agenda Semanal, Pagos y Reportes.
 */
final class TurnoCanceladoPorRegeneracion
{
    /**
     * @param  list<int>  $turnoIds
     */
    public function __construct(
        public readonly int $loteId,
        public readonly TipoOrigenTurno $tipoOrigen,
        public readonly int $origenId,
        public readonly int $sucursalId,
        public readonly array $turnoIds,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
