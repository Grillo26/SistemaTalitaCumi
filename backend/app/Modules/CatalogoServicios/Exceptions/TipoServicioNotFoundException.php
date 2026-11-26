<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class TipoServicioNotFoundException extends CatalogoServiciosDomainException
{
    public function __construct(public readonly int $tipoServicioId)
    {
        parent::__construct('El tipo de servicio indicado no existe.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'TIPO_SERVICIO_NOT_FOUND';
    }
}
