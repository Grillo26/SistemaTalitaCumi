<?php

namespace App\Modules\Organizacion\Exceptions;

final class TipoSalaNotFoundException extends OrganizacionDomainException
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró el tipo de sala solicitado (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'TIPO_SALA_NOT_FOUND';
    }
}
