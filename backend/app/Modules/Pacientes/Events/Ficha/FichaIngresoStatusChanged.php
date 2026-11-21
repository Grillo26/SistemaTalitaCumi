<?php

namespace App\Modules\Pacientes\Events\Ficha;

/**
 * Cambio de estado de la ficha: completar, archivar o reactivar (04.03/RN-005).
 */
final readonly class FichaIngresoStatusChanged
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $fichaId,
        public int $pacienteId,
        public array $before,
        public array $after,
        public string $accion,
        public bool $critico,
        public ?string $motivo,
        public int $revisionId,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
