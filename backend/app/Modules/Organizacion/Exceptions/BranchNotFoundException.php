<?php

namespace App\Modules\Organizacion\Exceptions;

final class BranchNotFoundException extends OrganizacionDomainException
{
    public function __construct(int $branchId)
    {
        parent::__construct("No se encontró la sucursal solicitada (ID {$branchId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'BRANCH_NOT_FOUND';
    }
}
