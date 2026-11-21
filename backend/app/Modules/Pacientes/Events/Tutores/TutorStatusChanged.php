<?php

namespace App\Modules\Pacientes\Events\Tutores;

/**
 * Cambio de estado del tutor (04.02/RF-010).
 */
final readonly class TutorStatusChanged
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $tutorId,
        public array $before,
        public array $after,
        public bool $activo,
        public ?string $motivo,
        public bool $critico,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
