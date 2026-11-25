<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteConcurrencyException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El paciente fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_VERSION_CONFLICT';
    }
}
