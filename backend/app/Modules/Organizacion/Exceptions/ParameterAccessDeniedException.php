<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterAccessDeniedException extends OrganizacionDomainException
{
    public function __construct()
    {
        parent::__construct('No tenés acceso a este parámetro de configuración.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_ACCESS_DENIED';
    }
}
