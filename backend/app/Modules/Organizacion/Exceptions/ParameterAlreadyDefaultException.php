<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterAlreadyDefaultException extends OrganizacionDomainException
{
    public function __construct(string $clave)
    {
        parent::__construct("El parámetro '{$clave}' ya tiene su valor por defecto.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_YA_EN_DEFECTO';
    }
}
