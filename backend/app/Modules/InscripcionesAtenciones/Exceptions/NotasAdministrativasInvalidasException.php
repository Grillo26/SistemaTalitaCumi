<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class NotasAdministrativasInvalidasException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $message = 'Las notas administrativas no pueden superar los 500 caracteres.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ATENCION_NOTAS_INVALIDAS';
    }
}
