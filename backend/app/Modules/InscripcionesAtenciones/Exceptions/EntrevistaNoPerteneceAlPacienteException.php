<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class EntrevistaNoPerteneceAlPacienteException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $entrevistaId, public readonly int $pacienteId)
    {
        parent::__construct('La entrevista indicada no pertenece al paciente del diagnóstico.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ENTREVISTA_NO_PERTENECE_AL_PACIENTE';
    }
}
