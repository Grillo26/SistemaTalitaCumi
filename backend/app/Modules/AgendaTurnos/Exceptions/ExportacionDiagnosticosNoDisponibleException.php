<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-008 / 07.05/RNF-010 — PDF pendiente de infraestructura.
 */
final class ExportacionDiagnosticosNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct('La exportación PDF de diagnósticos no está disponible: falta infraestructura.');
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'EXPORTACION_DIAGNOSTICOS_NO_DISPONIBLE';
    }
}
