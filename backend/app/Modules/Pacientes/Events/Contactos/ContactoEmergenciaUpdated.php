<?php

namespace App\Modules\Pacientes\Events\Contactos;

/**
 * Contacto de emergencia actualizado (04.02/RF-012).
 */
final readonly class ContactoEmergenciaUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $contactoId,
        public int $pacienteId,
        public array $before,
        public array $after,
        public array $changedFields,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
