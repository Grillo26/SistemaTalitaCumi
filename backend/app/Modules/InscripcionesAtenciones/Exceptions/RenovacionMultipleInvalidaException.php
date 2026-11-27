<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class RenovacionMultipleInvalidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'RENOVACION_MULTIPLE_INVALIDA';
    }
}
