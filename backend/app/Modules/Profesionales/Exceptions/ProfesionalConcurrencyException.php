<?php

namespace App\Modules\Profesionales\Exceptions;

final class ProfesionalConcurrencyException extends ProfesionalesDomainException
{
    public function __construct()
    {
        parent::__construct('El profesional fue modificado por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_VERSION_CONFLICT';
    }
}
