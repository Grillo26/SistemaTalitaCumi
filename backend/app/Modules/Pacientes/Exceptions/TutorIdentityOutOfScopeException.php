<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorIdentityOutOfScopeException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tutor está asociado a pacientes fuera de tu alcance. Solo podés editar sus datos de contacto.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'TUTOR_IDENTITY_OUT_OF_SCOPE';
    }
}
