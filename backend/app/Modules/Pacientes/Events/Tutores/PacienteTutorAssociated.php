<?php

namespace App\Modules\Pacientes\Events\Tutores;

/**
 * Tutor asociado a un paciente (04.02/RF-004).
 */
final readonly class PacienteTutorAssociated
{
    /**
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public int $tutorId,
        public int $parentescoId,
        public bool $esPrincipal,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
