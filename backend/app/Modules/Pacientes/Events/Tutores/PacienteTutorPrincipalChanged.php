<?php

namespace App\Modules\Pacientes\Events\Tutores;

/**
 * Cambio de tutor principal (04.02/RF-005).
 */
final readonly class PacienteTutorPrincipalChanged
{
    /**
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public int $tutorId,
        public ?int $anteriorTutorId,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
