<?php

namespace App\Modules\Pacientes\Events\Consentimiento;

/**
 * Consentimiento revocado mediante un evento nuevo (04.03 Fase B).
 */
final readonly class ConsentimientoRevoked
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $consentimientoId,
        public int $originalId,
        public int $fichaId,
        public int $pacienteId,
        public array $snapshot,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
