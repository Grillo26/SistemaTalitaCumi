<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoLineasInvalidasException extends PagosDomainException
{
    public function __construct(string $message = 'El pago debe tener una o dos líneas con medios distintos y disponibles.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PAGO_LINEAS_INVALIDAS';
    }
}
