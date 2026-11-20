<?php

namespace App\Modules\Organizacion\Exceptions;

final class StatusUnchangedException extends OrganizacionDomainException
{
    public function __construct(bool $targetStatus)
    {
        parent::__construct($targetStatus
            ? 'El registro ya se encuentra activo.'
            : 'El registro ya se encuentra inactivo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'STATUS_UNCHANGED';
    }
}
