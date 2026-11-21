<?php

namespace App\Modules\Pacientes\Exceptions;

/**
 * Documento duplicado: bloqueo duro, sin confirmación posible.
 *
 * El payload solo contiene datos del menor si está dentro del alcance del
 * actor; el presentador decide qué se expone.
 */
final class DuplicatePacienteDocumentException extends PacientesDomainException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data = [])
    {
        parent::__construct('El documento de identidad ya está registrado en otro paciente.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_DOCUMENT_DUPLICATE';
    }

    /**
     * @return array<string, mixed>
     */
    protected function errorContext(): array
    {
        return $this->data === [] ? [] : ['data' => $this->data];
    }
}
