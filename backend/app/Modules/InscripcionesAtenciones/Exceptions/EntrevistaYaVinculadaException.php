<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class EntrevistaYaVinculadaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $diagnosticoId)
    {
        parent::__construct('El diagnóstico ya tiene una entrevista vinculada.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ENTREVISTA_YA_VINCULADA';
    }
}
