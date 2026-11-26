<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class AtencionDeleteNotAllowedException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $atencionId)
    {
        parent::__construct('Las atenciones no se eliminan: se dan de baja con motivo y fecha.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ATENCION_DELETE_NOT_ALLOWED';
    }
}
