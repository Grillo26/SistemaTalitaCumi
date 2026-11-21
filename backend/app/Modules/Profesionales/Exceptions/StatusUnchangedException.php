<?php

namespace App\Modules\Profesionales\Exceptions;

final class StatusUnchangedException extends ProfesionalesDomainException
{
    public function __construct(bool $targetStatus, string $entityLabel)
    {
        parent::__construct($targetStatus
            ? ucfirst($entityLabel).' ya se encuentra activo.'
            : ucfirst($entityLabel).' ya se encuentra inactivo.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'STATUS_UNCHANGED';
    }
}
