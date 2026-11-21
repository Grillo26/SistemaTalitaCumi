<?php

namespace App\Modules\Organizacion\Exceptions;

final class EspacioNotFoundException extends OrganizacionDomainException
{
    public function __construct(string $entityLabel, int $id)
    {
        parent::__construct("No se encontró el/la {$entityLabel} solicitado/a (ID {$id}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ESPACIO_NOT_FOUND';
    }
}
