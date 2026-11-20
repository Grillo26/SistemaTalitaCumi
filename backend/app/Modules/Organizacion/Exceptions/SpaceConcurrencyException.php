<?php

namespace App\Modules\Organizacion\Exceptions;

final class SpaceConcurrencyException extends OrganizacionDomainException
{
    public function __construct(string $entityLabel)
    {
        parent::__construct("El registro de {$entityLabel} fue modificado por otro usuario. Recargá los datos antes de guardar.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'SPACE_VERSION_CONFLICT';
    }
}
