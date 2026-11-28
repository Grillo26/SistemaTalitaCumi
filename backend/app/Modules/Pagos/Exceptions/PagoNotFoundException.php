<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoNotFoundException extends PagosDomainException
{
    public function __construct(string $message = 'No se encontró el pago solicitado.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'PAGO_NOT_FOUND';
    }
}
