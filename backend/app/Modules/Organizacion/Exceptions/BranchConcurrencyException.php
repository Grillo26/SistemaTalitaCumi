<?php

namespace App\Modules\Organizacion\Exceptions;

final class BranchConcurrencyException extends OrganizacionDomainException
{
    public function __construct()
    {
        parent::__construct('La sucursal fue modificada por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'BRANCH_VERSION_CONFLICT';
    }
}
