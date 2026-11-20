<?php

namespace App\Modules\Organizacion\Exceptions;

final class DeletionNotAllowedException extends OrganizacionDomainException
{
    public function __construct(
        private readonly string $entityLabel,
        string $name,
        private readonly string $deletionCode = 'SPACE_DELETE_NOT_ALLOWED',
    ) {
        parent::__construct(sprintf(
            'No se puede eliminar %s "%s". Usá la acción Desactivar para conservar el historial.',
            $this->entityLabel,
            $name,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return $this->deletionCode;
    }
}
