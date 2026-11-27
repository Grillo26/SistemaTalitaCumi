<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

/**
 * [Estados Paciente e Inscripción] CU-01 FE-02.
 */
final class InscripcionVencidaNoPausableException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct('La inscripción está vencida. Renovala antes de pausarla.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_VENCIDA_NO_PAUSABLE';
    }
}
