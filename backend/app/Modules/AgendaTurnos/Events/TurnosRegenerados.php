<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

/**
 * Turnos regenerados: cancelación de futuros programados y creación de los
 * nuevos (RF-010 / RF-011 / RF-012).
 */
final class TurnosRegenerados
{
    /**
     * @param  list<int>  $turnosCreadosIds
     * @param  list<int>  $turnosCanceladosIds
     * @param  list<string>  $advertencias  advertencias confirmadas
     */
    public function __construct(
        public readonly int $loteId,
        public readonly TipoOrigenTurno $tipoOrigen,
        public readonly int $origenId,
        public readonly int $sucursalId,
        public readonly int $cantidadCreados,
        public readonly int $cantidadCancelados,
        public readonly int $cantidadPreservados,
        public readonly array $turnosCreadosIds,
        public readonly array $turnosCanceladosIds,
        public readonly array $advertencias,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
