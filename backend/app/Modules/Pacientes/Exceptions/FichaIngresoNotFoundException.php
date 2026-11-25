<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoNotFoundException extends PacientesDomainException
{
    public function __construct(int $fichaId)
    {
        parent::__construct("No se encontró la ficha de ingreso solicitada (ID {$fichaId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'FICHA_NOT_FOUND';
    }
}
