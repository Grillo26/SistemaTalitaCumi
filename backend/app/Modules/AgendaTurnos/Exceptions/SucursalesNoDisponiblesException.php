<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class SucursalesNoDisponiblesException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct('No se pudieron resolver las sucursales disponibles.');
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'SUCURSALES_NO_DISPONIBLES';
    }
}
