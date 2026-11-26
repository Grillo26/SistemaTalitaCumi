<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class AtencionFechasInvalidasException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'Las fechas de la atención no son válidas.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ATENCION_FECHAS_INVALIDAS';
    }
}
