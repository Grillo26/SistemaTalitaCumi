<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoMontoInvalidoException extends PagosDomainException
{
    public function __construct(string $message = 'El monto debe ser mayor que cero, con hasta 2 decimales.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PAGO_MONTO_INVALIDO';
    }
}
