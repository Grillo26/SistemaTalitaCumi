<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.04/RF-005 — corrección fuera de ventana sin motivo.
 */
final class MotivoAsistenciaRequeridoException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'La corrección fuera de la ventana exige un motivo.',
        );
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'MOTIVO_ASISTENCIA_REQUERIDO';
    }
}
