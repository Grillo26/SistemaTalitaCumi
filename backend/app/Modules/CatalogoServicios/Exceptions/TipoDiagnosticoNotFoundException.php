<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TipoDiagnosticoNotFoundException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $tipoDiagnosticoId)
    {
        parent::__construct('El tipo de diagnóstico indicado no existe.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_NOT_FOUND';
    }
}
