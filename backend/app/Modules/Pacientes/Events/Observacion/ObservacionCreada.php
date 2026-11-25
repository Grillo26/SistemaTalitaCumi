<?php

namespace App\Modules\Pacientes\Events\Observacion;

/**
 * Observación fechada creada (04.03 Fase C, RF-006).
 */
final readonly class ObservacionCreada
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $observacionId,
        public int $fichaId,
        public int $pacienteId,
        public array $snapshot,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
