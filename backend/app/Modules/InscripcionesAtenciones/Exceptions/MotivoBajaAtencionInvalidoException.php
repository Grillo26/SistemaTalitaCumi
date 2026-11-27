<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class MotivoBajaAtencionInvalidoException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'El motivo de baja no es válido.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'MOTIVO_BAJA_ATENCION_INVALIDO';
    }
}
