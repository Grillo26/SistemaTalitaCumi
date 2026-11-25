<?php

namespace App\Modules\Pacientes\Exceptions;

final class TutorSucursalRequiredException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Indicá la sucursal de registro del tutor. Es obligatoria cuando el usuario tiene alcance global o varias sucursales.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TUTOR_SUCURSAL_REQUIRED';
    }
}
