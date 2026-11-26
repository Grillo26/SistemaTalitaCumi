<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class CatalogoStatusUnchangedException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('El registro ya tiene el estado solicitado.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'STATUS_UNCHANGED';
    }
}
