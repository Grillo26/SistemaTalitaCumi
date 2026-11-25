<?php

namespace App\Modules\Pacientes\Events\Tutores;

/**
 * Tutor actualizado (04.02/RF-009, 04.02/RF-014).
 */
final readonly class TutorUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     * @param  list<int|null>  $branchIds
     */
    public function __construct(
        public int $tutorId,
        public array $before,
        public array $after,
        public array $changedFields,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
