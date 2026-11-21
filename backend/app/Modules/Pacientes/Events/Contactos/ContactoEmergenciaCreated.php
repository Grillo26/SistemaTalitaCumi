<?php

namespace App\Modules\Pacientes\Events\Contactos;

/**
 * Contacto de emergencia creado (04.02/RF-012).
 */
final readonly class ContactoEmergenciaCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<int>  $branchIds
     */
    public function __construct(
        public int $contactoId,
        public int $pacienteId,
        public array $snapshot,
        public array $branchIds,
        public ?int $actorId,
        public ?string $ip,
    ) {}
}
