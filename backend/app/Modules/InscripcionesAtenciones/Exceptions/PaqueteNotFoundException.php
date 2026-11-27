<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class PaqueteNotFoundException extends InscripcionesAtencionesDomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró el paquete solicitado (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PAQUETE_NOT_FOUND';
    }
}
