<?php

namespace App\Modules\Pacientes\Exceptions;

final class ContactoEmergenciaNotFoundException extends PacientesDomainException
{
    public function __construct(int $contactoId)
    {
        parent::__construct("No se encontró el contacto de emergencia solicitado (ID {$contactoId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'CONTACT_EMERGENCY_NOT_FOUND';
    }
}
