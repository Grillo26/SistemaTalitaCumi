<?php

namespace App\Modules\Pacientes\Events\Consentimiento;

/**
 * Tipo de consentimiento creado en el catálogo (04.03 Fase B).
 */
final readonly class ConsentimientoTipoCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public int $tipoId,
        public array $snapshot,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
