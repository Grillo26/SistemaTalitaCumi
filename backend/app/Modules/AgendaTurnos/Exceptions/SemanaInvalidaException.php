<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class SemanaInvalidaException extends AgendaTurnosDomainException
{
    public function __construct(?string $fecha = null)
    {
        parent::__construct(
            $fecha === null
                ? 'La fecha de la semana no es válida.'
                : "La fecha «{$fecha}» no es una fecha válida para la agenda.",
        );
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'SEMANA_INVALIDA';
    }
}
