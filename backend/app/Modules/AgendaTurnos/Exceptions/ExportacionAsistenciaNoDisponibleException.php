<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.04/RF-010 / RNF-010 — PDF pendiente de infraestructura.
 */
final class ExportacionAsistenciaNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'La exportación PDF de asistencia no está disponible: falta instalar la infraestructura PDF.',
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'EXPORTACION_ASISTENCIA_NO_DISPONIBLE';
    }
}
