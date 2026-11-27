<?php

namespace App\Modules\AgendaTurnos\Events;

/**
 * Profesional asignado a un lote o subconjunto de turnos (RF-007).
 */
final class ProfesionalAsignadoALote
{
    /**
     * @param  list<int>  $turnoIds
     */
    public function __construct(
        public readonly ?int $loteId,
        public readonly int $profesionalId,
        public readonly int $sucursalId,
        public readonly array $turnoIds,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
