<?php

namespace App\Modules\Organizacion\Exceptions;

final class BranchStatusUnchangedException extends OrganizacionDomainException
{
    public function __construct(bool $targetStatus)
    {
        parent::__construct($targetStatus
            ? 'La sucursal ya se encuentra activa.'
            : 'La sucursal ya se encuentra inactiva.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'BRANCH_STATUS_UNCHANGED';
    }
}
