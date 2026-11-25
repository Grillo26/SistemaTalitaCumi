<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteResumenSucursalInactivaException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La sucursal de contexto está inactiva.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'RESUMEN_SUCURSAL_INACTIVA';
    }
}
