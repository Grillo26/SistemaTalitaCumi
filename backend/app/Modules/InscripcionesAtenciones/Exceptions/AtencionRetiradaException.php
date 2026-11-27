<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;

final class AtencionRetiradaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly EstadoAtencion $estado)
    {
        parent::__construct('La atención está retirada y no puede modificarse ni darse de baja nuevamente.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ATENCION_RETIRADA';
    }
}
