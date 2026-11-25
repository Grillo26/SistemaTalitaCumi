<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorParentescoInvalidException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El parentesco seleccionado no existe o está inactivo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TUTOR_PARENTESCO_INVALID';
    }
}
