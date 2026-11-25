<?php

namespace App\Modules\Pacientes\Exceptions;

final class ContactoEmergenciaConcurrencyException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El contacto de emergencia fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONTACT_EMERGENCY_VERSION_CONFLICT';
    }
}
