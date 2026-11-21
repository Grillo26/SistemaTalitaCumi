<?php

namespace App\Modules\Pacientes\Events\Tutores;

/**
 * Tutor creado (04.02/RF-001, 04.02/RF-014).
 */
final readonly class TutorCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int|null>  $branchIds
     */
    public function __construct(
        public int $tutorId,
        public array $snapshot,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
