<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class AtencionDatosIncompletosException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly array $campos = [])
    {
        parent::__construct('Faltan datos obligatorios para registrar la atención.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ATENCION_DATOS_INCOMPLETOS';
    }
}
