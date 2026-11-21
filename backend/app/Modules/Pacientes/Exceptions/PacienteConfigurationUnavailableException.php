<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteConfigurationUnavailableException extends PacientesDomainException
{
    public function __construct(string $clave)
    {
        parent::__construct("Falta el parámetro de configuración '{$clave}' necesario para calcular la edad del paciente.");
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'PATIENT_CONFIGURATION_UNAVAILABLE';
    }
}
