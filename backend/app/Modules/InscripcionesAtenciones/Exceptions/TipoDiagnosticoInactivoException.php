<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class TipoDiagnosticoInactivoException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $tipoDiagnosticoId)
    {
        parent::__construct('El tipo de diagnóstico está inactivo y no puede usarse en nuevas atenciones.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_INACTIVO';
    }
}
