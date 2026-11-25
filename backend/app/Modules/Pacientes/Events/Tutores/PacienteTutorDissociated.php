<?php

namespace App\Modules\Pacientes\Events\Tutores;

/**
 * Tutor desasociado de un paciente (04.02/RF-006).
 */
final readonly class PacienteTutorDissociated
{
    /**
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public int $tutorId,
        public bool $eraPrincipal,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
