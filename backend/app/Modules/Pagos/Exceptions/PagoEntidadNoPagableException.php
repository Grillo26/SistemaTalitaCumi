<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoEntidadNoPagableException extends PagosDomainException
{
    public function __construct(string $message = 'La entidad está retirada o anulada y no admite pagos.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ENTIDAD_NO_PAGABLE';
    }
}
