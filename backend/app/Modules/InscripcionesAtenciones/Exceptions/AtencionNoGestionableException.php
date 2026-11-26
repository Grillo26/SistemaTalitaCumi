<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;

final class AtencionNoGestionableException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly EstadoAtencion $estado)
    {
        parent::__construct('La atención no admite la operación solicitada en su estado actual.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ATENCION_NO_GESTIONABLE';
    }
}
