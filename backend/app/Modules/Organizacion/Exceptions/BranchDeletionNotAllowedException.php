<?php

namespace App\Modules\Organizacion\Exceptions;

final class BranchDeletionNotAllowedException extends OrganizacionDomainException
{
    public function __construct(string $branchName)
    {
        parent::__construct(sprintf(
            'No se puede eliminar la sucursal "%s". Usá la acción Desactivar para conservar el historial.',
            $branchName,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'BRANCH_DELETE_NOT_ALLOWED';
    }
}
