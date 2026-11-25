<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorNotFoundException extends PacientesDomainException
{
    public function __construct(int $tutorId)
    {
        parent::__construct("No se encontró el tutor solicitado (ID {$tutorId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'TUTOR_NOT_FOUND';
    }
}
