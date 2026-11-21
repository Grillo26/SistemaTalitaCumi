<?php

namespace App\Modules\Profesionales\Exceptions;

final class ProfesionalSucursalNotFoundException extends ProfesionalesDomainException
{
    public function __construct(int $profesionalId, int $sucursalId)
    {
        parent::__construct("No se encontró la relación del profesional {$profesionalId} con la sucursal {$sucursalId}.");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_SUCURSAL_NOT_FOUND';
    }
}
