<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class SinSucursalesAsignadasException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'No tenés sucursales asignadas. Contactá al Superadmin para que te asigne al menos una.',
        );
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'SIN_SUCURSALES_ASIGNADAS';
    }
}
