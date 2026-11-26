<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class AreaDeletionNotAllowedException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('Las áreas no se eliminan. Desactivala para conservar el historial.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'AREA_DELETE_NOT_ALLOWED';
    }
}
