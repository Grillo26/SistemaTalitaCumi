<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoDeleteNotAllowedException extends PagosDomainException
{
    public function __construct(string $message = 'Los pagos no se eliminan: se anulan con motivo (RN-006).')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAGO_DELETE_NOT_ALLOWED';
    }
}
