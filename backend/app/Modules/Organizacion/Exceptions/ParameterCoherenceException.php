<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterCoherenceException extends OrganizacionDomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_INCOHERENTE';
    }
}
