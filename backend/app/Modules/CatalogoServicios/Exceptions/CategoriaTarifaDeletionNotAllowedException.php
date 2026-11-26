<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class CategoriaTarifaDeletionNotAllowedException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('Las categorías de tarifa no se eliminan. Desactivalas para conservar el historial.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CATEGORIA_TARIFA_DELETE_NOT_ALLOWED';
    }
}
