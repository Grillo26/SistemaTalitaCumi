<?php

namespace App\Modules\Pacientes\Events\Ficha;

/**
 * Ficha de ingreso creada (04.03/RF-001).
 */
final readonly class FichaIngresoCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $fichaId,
        public int $pacienteId,
        public array $snapshot,
        public int $revisionId,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
