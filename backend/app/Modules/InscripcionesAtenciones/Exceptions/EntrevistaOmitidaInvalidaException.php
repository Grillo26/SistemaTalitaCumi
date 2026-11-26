<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class EntrevistaOmitidaInvalidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'Indicá si la entrevista se omite o vinculá una entrevista previa.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ENTREVISTA_OMITIDA_INVALIDA';
    }
}
