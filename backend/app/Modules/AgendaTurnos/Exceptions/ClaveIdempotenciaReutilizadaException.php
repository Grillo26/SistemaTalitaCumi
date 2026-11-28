<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class ClaveIdempotenciaReutilizadaException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'La clave de idempotencia ya fue usada para otra entidad origen o acción.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CLAVE_IDEMPOTENCIA_REUTILIZADA';
    }
}
