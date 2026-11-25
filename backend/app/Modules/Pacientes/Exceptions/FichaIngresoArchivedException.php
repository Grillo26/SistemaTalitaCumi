<?php

namespace App\Modules\Pacientes\Exceptions;

final class FichaIngresoArchivedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('La ficha de ingreso está archivada y no admite cambios. Reactivala antes de editarla.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'FICHA_ARCHIVED';
    }
}
