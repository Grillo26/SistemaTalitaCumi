<?php

namespace App\Modules\Profesionales\Exceptions;

final class ProfesionalInactivoException extends ProfesionalesDomainException
{
    public function __construct(int $profesionalId)
    {
        parent::__construct("El profesional solicitado está inactivo (ID {$profesionalId}) y no puede asignarse a nuevos turnos o diagnósticos.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_INACTIVE';
    }
}
