<?php

namespace App\Modules\Profesionales\Exceptions;

final class EspecialidadNotFoundException extends ProfesionalesDomainException
{
    public function __construct(int $especialidadId)
    {
        parent::__construct("No se encontró la especialidad solicitada (ID {$especialidadId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ESPECIALIDAD_NOT_FOUND';
    }
}
