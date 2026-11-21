<?php

namespace App\Modules\Organizacion\Exceptions;

final class InactiveBranchException extends OrganizacionDomainException
{
    public function __construct(
        private readonly int $branchId,
        private readonly string $branchName,
    ) {
        parent::__construct(sprintf(
            'La sucursal "%s" está inactiva y no puede recibir nuevas operaciones.',
            $branchName,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'BRANCH_INACTIVE';
    }

    public function branchId(): int
    {
        return $this->branchId;
    }
}
