<?php

namespace App\Modules\Profesionales\Exceptions;

final class ProfesionalSucursalConcurrencyException extends ProfesionalesDomainException
{
    public function __construct()
    {
        parent::__construct('La relación con la sucursal fue modificada por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_SUCURSAL_VERSION_CONFLICT';
    }
}
