<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorParentescoRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El parentesco es obligatorio para asociar un tutor a un paciente.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TUTOR_PARENTESCO_REQUIRED';
    }
}
