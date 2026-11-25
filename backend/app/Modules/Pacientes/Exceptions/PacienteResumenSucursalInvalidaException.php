<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteResumenSucursalInvalidaException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La sucursal de contexto no está disponible para tu alcance.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'RESUMEN_SUCURSAL_INVALIDA';
    }
}
