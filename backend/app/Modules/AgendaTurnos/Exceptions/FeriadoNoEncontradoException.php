<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class FeriadoNoEncontradoException extends AgendaTurnosDomainException
{
    public function __construct(public readonly int $feriadoId)
    {
        parent::__construct("No se encontró el feriado #{$feriadoId}.");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'FERIADO_NO_ENCONTRADO';
    }
}
