<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterNotFoundException extends OrganizacionDomainException
{
    public function __construct(string|int $identifier)
    {
        $message = is_int($identifier)
            ? "No se encontró el parámetro solicitado (ID {$identifier})."
            : "No se encontró el parámetro de configuración '{$identifier}'.";

        parent::__construct($message);
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_NOT_FOUND';
    }
}
