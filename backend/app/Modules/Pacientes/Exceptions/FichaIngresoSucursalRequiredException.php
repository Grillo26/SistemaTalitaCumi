<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoSucursalRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Indicá la sucursal de ingreso de la ficha. Es obligatoria cuando tenés alcance global o varias sucursales.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'FICHA_SUCURSAL_REQUIRED';
    }
}
