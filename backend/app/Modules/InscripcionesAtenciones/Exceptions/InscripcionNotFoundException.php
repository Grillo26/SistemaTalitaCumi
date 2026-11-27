<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class InscripcionNotFoundException extends InscripcionesAtencionesDomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró la inscripción solicitada (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_NOT_FOUND';
    }
}
