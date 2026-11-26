<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TarifaNotFoundException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $tarifaId)
    {
        parent::__construct('La tarifa indicada no existe.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'TARIFA_NOT_FOUND';
    }
}
