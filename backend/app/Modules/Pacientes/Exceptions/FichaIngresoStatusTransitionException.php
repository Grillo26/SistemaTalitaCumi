<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoStatusTransitionException extends PacientesDomainException
{
    public function __construct(string $message = 'La transición de estado de la ficha no está permitida.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'FICHA_STATUS_TRANSITION_NOT_ALLOWED';
    }
}
