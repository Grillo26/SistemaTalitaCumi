<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

/**
 * [Estados Paciente e Inscripción] RN-003 / punto 4 de la especificación.
 */
final class InscripcionVencidaNoRetirableException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct('La inscripción está vencida y no puede retirarse; debe renovarse.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_VENCIDA_NO_RETIRABLE';
    }
}
