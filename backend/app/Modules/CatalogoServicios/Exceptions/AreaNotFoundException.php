<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class AreaNotFoundException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $areaId)
    {
        parent::__construct('El área indicada no existe.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'AREA_NOT_FOUND';
    }
}
