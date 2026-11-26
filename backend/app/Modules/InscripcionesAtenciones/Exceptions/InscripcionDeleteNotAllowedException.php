<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class InscripcionDeleteNotAllowedException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct(
            'Las inscripciones no se eliminan. Usá la baja con motivo para preservar el historial.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_DELETE_NOT_ALLOWED';
    }
}
