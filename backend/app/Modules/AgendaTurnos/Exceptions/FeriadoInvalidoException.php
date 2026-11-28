<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class FeriadoInvalidoException extends AgendaTurnosDomainException
{
    public function __construct(public readonly string $detalle)
    {
        parent::__construct($detalle);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'FERIADO_INVALIDO';
    }
}
