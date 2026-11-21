<?php

namespace App\Modules\Profesionales\Exceptions;

final class ProfesionalSucursalDuplicatedException extends ProfesionalesDomainException
{
    public function __construct(bool $activa)
    {
        parent::__construct($activa
            ? 'El profesional ya tiene una relación activa con esa sucursal.'
            : 'Ya existe una relación inactiva con esa sucursal. Usá la acción Activar para rehabilitarla.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_SUCURSAL_DUPLICATED';
    }
}
