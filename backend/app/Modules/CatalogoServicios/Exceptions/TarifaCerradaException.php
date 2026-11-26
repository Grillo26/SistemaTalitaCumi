<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TarifaCerradaException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('La tarifa está cerrada y no se puede editar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TARIFA_CERRADA';
    }
}
