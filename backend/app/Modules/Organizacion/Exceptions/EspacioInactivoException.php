<?php

namespace App\Modules\Organizacion\Exceptions;

final class EspacioInactivoException extends OrganizacionDomainException
{
    public function __construct(
        private readonly string $entityLabel,
        private readonly int $spaceId,
        private readonly string $spaceName,
    ) {
        parent::__construct(sprintf(
            'El/la %s "%s" está inactivo/a y no admite nuevas operaciones.',
            $this->entityLabel,
            $this->spaceName,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ESPACIO_INACTIVO';
    }

    public function spaceId(): int
    {
        return $this->spaceId;
    }
}
