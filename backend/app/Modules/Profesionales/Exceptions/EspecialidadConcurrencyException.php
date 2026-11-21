<?php

namespace App\Modules\Profesionales\Exceptions;

final class EspecialidadConcurrencyException extends ProfesionalesDomainException
{
    public function __construct()
    {
        parent::__construct('La especialidad fue modificada por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ESPECIALIDAD_VERSION_CONFLICT';
    }
}
