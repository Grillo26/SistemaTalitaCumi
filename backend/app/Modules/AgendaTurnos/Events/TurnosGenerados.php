<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

/**
 * Turnos creados desde una entidad origen (RF-002 / RF-012).
 *
 * Payload mínimo para bitácora, Pagos, Reportes y los submódulos 7.3/7.4.
 */
final class TurnosGenerados
{
    /**
     * @param  list<int>  $turnoIds
     * @param  list<string>  $advertencias  advertencias confirmadas
     */
    public function __construct(
        public readonly int $loteId,
        public readonly TipoOrigenTurno $tipoOrigen,
        public readonly int $origenId,
        public readonly int $sucursalId,
        public readonly int $cantidadCreados,
        public readonly array $turnoIds,
        public readonly array $advertencias,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
