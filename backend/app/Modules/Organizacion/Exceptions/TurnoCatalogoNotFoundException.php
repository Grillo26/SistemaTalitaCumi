<?php

namespace App\Modules\Organizacion\Exceptions;

final class TurnoCatalogoNotFoundException extends OrganizacionDomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró el turno solicitado (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'TURNO_NOT_FOUND';
    }
}
