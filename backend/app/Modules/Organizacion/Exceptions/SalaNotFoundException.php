<?php

namespace App\Modules\Organizacion\Exceptions;

final class SalaNotFoundException extends OrganizacionDomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró la sala solicitada (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'SALA_NOT_FOUND';
    }
}
