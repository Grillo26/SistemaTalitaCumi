<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-010 / 07.05/RN-008 / 07.05/CA-009.
 *
 * Un diagnóstico inexistente o fuera del alcance del usuario responde igual
 * (404) para no revelar su existencia.
 */
final class DiagnosticoNotFoundException extends AgendaTurnosDomainException
{
    public function __construct(public readonly int $diagnosticoId)
    {
        parent::__construct('No se encontró el diagnóstico solicitado.');
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'DIAGNOSTICO_NO_ENCONTRADO';
    }
}
