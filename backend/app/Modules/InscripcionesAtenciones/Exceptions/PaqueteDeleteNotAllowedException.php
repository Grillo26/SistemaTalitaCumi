<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class PaqueteDeleteNotAllowedException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct(
            'Los paquetes no se eliminan. Usá la baja con motivo para preservar el historial.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAQUETE_DELETE_NOT_ALLOWED';
    }
}
