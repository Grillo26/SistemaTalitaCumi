<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoEntidadNoEncontradaException extends PagosDomainException
{
    public function __construct(string $message = 'No se encontró la entidad a pagar o no tenés acceso a su sucursal.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ENTIDAD_NOT_FOUND';
    }
}
