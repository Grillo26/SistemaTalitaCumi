<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorConcurrencyException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('El tutor fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_VERSION_CONFLICT';
    }
}
