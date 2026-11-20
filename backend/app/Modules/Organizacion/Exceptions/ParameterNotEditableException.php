<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterNotEditableException extends OrganizacionDomainException
{
    public function __construct(string $clave)
    {
        parent::__construct("El parámetro '{$clave}' es de solo lectura y no puede editarse desde esta pantalla.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_NO_EDITABLE';
    }
}
