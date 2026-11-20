<?php

namespace App\Modules\Organizacion\Exceptions;

final class AmbienteNotFoundException extends OrganizacionDomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró el ambiente solicitado (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'AMBIENTE_NOT_FOUND';
    }
}
