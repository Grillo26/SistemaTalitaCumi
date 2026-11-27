<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

/**
 * [Estados Paciente e Inscripción] RN-004 / CA-005.
 */
final class MotivoEstadoInvalidoException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'El motivo es obligatorio y debe pertenecer al catálogo.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'MOTIVO_ESTADO_INVALIDO';
    }
}
