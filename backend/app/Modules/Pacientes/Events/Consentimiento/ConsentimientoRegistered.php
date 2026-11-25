<?php

namespace App\Modules\Pacientes\Events\Consentimiento;

/**
 * Consentimiento registrado (04.03 Fase B, RF-003).
 */
final readonly class ConsentimientoRegistered
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $consentimientoId,
        public int $fichaId,
        public int $pacienteId,
        public string $resultado,
        public array $snapshot,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
