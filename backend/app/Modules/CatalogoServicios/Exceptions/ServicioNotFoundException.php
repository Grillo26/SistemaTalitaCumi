<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class ServicioNotFoundException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $servicioId)
    {
        parent::__construct('El servicio indicado no existe.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'SERVICIO_NOT_FOUND';
    }
}
