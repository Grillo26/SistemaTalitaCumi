<?php

namespace App\Modules\Organizacion\Exceptions;

final class BranchAccessDeniedException extends OrganizacionDomainException
{
    public function __construct()
    {
        parent::__construct('No tenés acceso a esta sucursal.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'BRANCH_ACCESS_DENIED';
    }
}
