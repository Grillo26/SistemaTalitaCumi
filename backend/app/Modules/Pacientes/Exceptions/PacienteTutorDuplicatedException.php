<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteTutorDuplicatedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tutor ya está asociado a este paciente.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PACIENTE_TUTOR_DUPLICATED';
    }
}
