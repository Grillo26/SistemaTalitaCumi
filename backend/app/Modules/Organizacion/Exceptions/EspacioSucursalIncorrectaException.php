<?php

namespace App\Modules\Organizacion\Exceptions;

final class EspacioSucursalIncorrectaException extends OrganizacionDomainException
{
    public function __construct(
        string $entityLabel,
        int $spaceId,
        private readonly int $expectedSucursalId,
        private readonly int $actualSucursalId,
    ) {
        parent::__construct(sprintf(
            'El/la %s (ID %d) no pertenece a la sucursal de la operación (esperada: %d, actual: %d).',
            $entityLabel,
            $spaceId,
            $this->expectedSucursalId,
            $this->actualSucursalId,
        ));
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ESPACIO_SUCURSAL_INCORRECTA';
    }
}
