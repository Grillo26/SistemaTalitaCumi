<?php

namespace App\Modules\Organizacion\Exceptions;

final class ParameterSnapshotNotFoundException extends OrganizacionDomainException
{
    public function __construct(int $id, string $fecha)
    {
        parent::__construct(
            "No hay registros de configuración para el parámetro {$id} en la fecha {$fecha}.",
        );
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_SIN_SNAPSHOT';
    }
}
