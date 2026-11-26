<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TarifaDeletionNotAllowedException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'Las tarifas no se eliminan. Cerrá la vigencia para conservar inscripciones y pagos históricos.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TARIFA_DELETE_NOT_ALLOWED';
    }
}
