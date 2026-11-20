<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterOutOfRangeException extends OrganizacionDomainException
{
    public function __construct(string $clave, string $message)
    {
        parent::__construct("El valor del parámetro '{$clave}' está fuera del rango permitido. {$message}");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_FUERA_DE_RANGO';
    }
}
