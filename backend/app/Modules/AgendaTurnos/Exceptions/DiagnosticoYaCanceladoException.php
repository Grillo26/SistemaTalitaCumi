<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-007 / 07.05/CU-02 FA-01.
 */
final class DiagnosticoYaCanceladoException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct('El diagnóstico ya está cancelado.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'DIAGNOSTICO_YA_CANCELADO';
    }
}
