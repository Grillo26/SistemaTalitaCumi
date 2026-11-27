<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class EntrevistaNoEncontradaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $entrevistaId)
    {
        parent::__construct("No se encontró la entrevista indicada (ID {$entrevistaId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ENTREVISTA_NO_ENCONTRADA';
    }
}
