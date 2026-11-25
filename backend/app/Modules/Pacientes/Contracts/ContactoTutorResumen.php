<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Contacto del tutor principal para la alerta de pago (04.02 Fase C).
 *
 * El contrato interno devuelve el teléfono completo; la capa HTTP lo
 * enmascara si el actor no tiene `tutor.ver_contacto`.
 */
final readonly class ContactoTutorResumen
{
    public function __construct(
        public int $tutorId,
        public string $nombreCompleto,
        public string $telefono,
        public ?string $parentesco = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tutor_id' => $this->tutorId,
            'tutor_nombre' => $this->nombreCompleto,
            'telefono' => $this->telefono,
            'parentesco' => $this->parentesco,
        ];
    }
}
