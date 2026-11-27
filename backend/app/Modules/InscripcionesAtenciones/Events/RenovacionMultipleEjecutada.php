<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * [Vigencia y Renovación] RF-016 / RNF-012.
 */
final class RenovacionMultipleEjecutada
{
    /**
     * @param  list<int>  $sucursalIds
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public readonly string $loteId,
        public readonly int $pacienteId,
        public readonly array $sucursalIds,
        public readonly array $items,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
