<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoFechaFuturaNoAutorizadaException extends PagosDomainException
{
    public function __construct(string $message = 'No tenés autorización para registrar un pago con fecha futura.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'PAGO_FECHA_FUTURA_NO_AUTORIZADA';
    }
}
