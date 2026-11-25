<?php

namespace App\Modules\Pacientes\Events\Registro;

/**
 * Paciente actualizado (04.01/RF-007, 04.01/RF-012).
 */
final readonly class PacienteUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     * @param  list<string>  $sensitiveFields
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public array $before,
        public array $after,
        public array $changedFields,
        public array $sensitiveFields,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
