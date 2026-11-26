<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TipoDiagnosticoDeletionNotAllowedException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('Los tipos de diagnóstico no se eliminan. Desactivalos para conservar el historial.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TIPO_DIAGNOSTICO_DELETE_NOT_ALLOWED';
    }
}
