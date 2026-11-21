<?php

namespace App\Modules\Pacientes\Events\Registro;

/**
 * Confirmación explícita de un duplicado potencial (04.01/RF-016).
 */
final readonly class PacienteDuplicateConfirmed
{
    /**
     * @param  list<int>  $coincidenciasEnAlcance
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $pacienteId,
        public string $motivo,
        public array $coincidenciasEnAlcance,
        public bool $huboFueraDeAlcance,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
