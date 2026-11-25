<?php

namespace App\Modules\Pacientes\Events\Registro;

/**
 * Paciente creado (04.01/RF-001, 04.01/RF-012).
 */
final readonly class PacienteCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public array $snapshot,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
