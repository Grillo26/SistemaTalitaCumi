<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterInvalidValueException extends OrganizacionDomainException
{
    public function __construct(string $clave, string $expected)
    {
        parent::__construct("El valor del parámetro '{$clave}' no es válido. Se esperaba {$expected}.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_VALOR_INVALIDO';
    }
}
