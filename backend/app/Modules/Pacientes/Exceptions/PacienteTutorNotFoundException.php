<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteTutorNotFoundException extends PacientesDomainException
{
    public function __construct(int $pacienteId, int $tutorId)
    {
        parent::__construct("El tutor {$tutorId} no está asociado al paciente {$pacienteId}.");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PACIENTE_TUTOR_NOT_FOUND';
    }
}
