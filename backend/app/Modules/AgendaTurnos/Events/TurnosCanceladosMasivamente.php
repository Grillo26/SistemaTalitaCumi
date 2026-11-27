<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Support\MotivoTurnoData;

/**
 * Registro de lote de una cancelación masiva.
 */
final class TurnosCanceladosMasivamente
{
    /**
     * @param  list<array{tipo: string, id: int}>  $entidades
     * @param  list<int>  $turnoIds
     * @param  list<int>  $sucursales
     */
    public function __construct(
        public readonly string $loteId,
        public readonly int $pacienteId,
        public readonly array $entidades,
        public readonly array $turnoIds,
        public readonly array $sucursales,
        public readonly MotivoTurnoData $motivo,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly ?string $motivoTexto = null,
    ) {}
}
