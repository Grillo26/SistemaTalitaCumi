<?php

namespace App\Modules\Pacientes\Events\Ficha;

/**
 * Ficha de ingreso actualizada (04.03/RF-005).
 */
final readonly class FichaIngresoUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $fichaId,
        public int $pacienteId,
        public array $before,
        public array $after,
        public array $changedFields,
        public int $revisionId,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
