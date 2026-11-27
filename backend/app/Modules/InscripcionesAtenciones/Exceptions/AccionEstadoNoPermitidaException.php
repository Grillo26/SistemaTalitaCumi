<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

/**
 * [Estados Paciente e Inscripción] CA-008.
 */
final class AccionEstadoNoPermitidaException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct('Acción no permitida para su rol.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'ACCION_NO_PERMITIDA';
    }
}
