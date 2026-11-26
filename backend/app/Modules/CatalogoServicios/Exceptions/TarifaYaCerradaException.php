<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TarifaYaCerradaException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('La tarifa ya tiene una fecha de cierre de vigencia.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TARIFA_YA_CERRADA';
    }
}
