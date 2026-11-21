<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorStatusUnchangedException extends PacientesDomainException
{
    public function __construct(bool $active)
    {
        parent::__construct($active
            ? 'El tutor ya se encuentra activo.'
            : 'El tutor ya se encuentra inactivo.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TUTOR_STATUS_UNCHANGED';
    }
}
