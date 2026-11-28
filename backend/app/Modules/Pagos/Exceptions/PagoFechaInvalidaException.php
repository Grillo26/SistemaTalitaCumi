<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoFechaInvalidaException extends PagosDomainException
{
    public function __construct(string $message = 'La fecha del pago no es válida.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PAGO_FECHA_INVALIDA';
    }
}
