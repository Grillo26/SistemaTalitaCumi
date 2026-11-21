<?php

namespace App\Modules\Profesionales\Exceptions;

final class ProfesionalNotFoundException extends ProfesionalesDomainException
{
    public function __construct(int $profesionalId)
    {
        parent::__construct("No se encontró el profesional solicitado (ID {$profesionalId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_NOT_FOUND';
    }
}
