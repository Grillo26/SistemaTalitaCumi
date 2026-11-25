<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoConcurrencyException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La ficha de ingreso fue modificada por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'FICHA_VERSION_CONFLICT';
    }
}
