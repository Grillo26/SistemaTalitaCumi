<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class AtencionDiagnosticoNotFoundException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $diagnosticoId)
    {
        parent::__construct("No se encontró el diagnóstico solicitado (ID {$diagnosticoId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ATENCION_DIAGNOSTICO_NOT_FOUND';
    }
}
