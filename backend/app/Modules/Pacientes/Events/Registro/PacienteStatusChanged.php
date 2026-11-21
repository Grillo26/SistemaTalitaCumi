<?php

namespace App\Modules\Pacientes\Events\Registro;

use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * Cambio de estado del paciente (04.01/RF-008, 04.01/RN-005).
 */
final readonly class PacienteStatusChanged
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public array $before,
        public array $after,
        public EstadoPaciente $from,
        public EstadoPaciente $to,
        public ?string $motivo,
        public bool $critico,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
