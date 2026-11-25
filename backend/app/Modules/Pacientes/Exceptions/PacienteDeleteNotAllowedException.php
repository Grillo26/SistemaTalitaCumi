<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteDeleteNotAllowedException extends PacientesDomainException
{
    public function __construct()
    {
        parent::__construct('Los pacientes no se eliminan. Cambiá su estado a retirado. El bloqueo por historial se valida cuando existan Inscripciones, Agenda y Pagos.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_DELETE_NOT_ALLOWED';
    }
}
