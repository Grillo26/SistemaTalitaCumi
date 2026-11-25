<?php

namespace App\Modules\Pacientes\Events\Consentimiento;

/**
 * Tipo de consentimiento actualizado en el catálogo (04.03 Fase B).
 */
final readonly class ConsentimientoTipoUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public int $tipoId,
        public array $before,
        public array $after,
        public array $changedFields,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
