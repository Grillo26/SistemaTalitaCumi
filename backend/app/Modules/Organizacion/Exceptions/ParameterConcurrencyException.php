<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterConcurrencyException extends OrganizacionDomainException
{
    public function __construct()
    {
        parent::__construct('El parámetro fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_VERSION_CONFLICT';
    }
}
