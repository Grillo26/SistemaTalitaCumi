<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class AtencionEntrevistaNotFoundException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $entrevistaId)
    {
        parent::__construct("No se encontró la entrevista solicitada (ID {$entrevistaId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ATENCION_ENTREVISTA_NOT_FOUND';
    }
}
