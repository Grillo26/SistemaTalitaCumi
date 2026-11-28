<?php

namespace App\Modules\Pagos\Exceptions;

use Throwable;

final class PagoAuditUnavailableException extends PagosDomainException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            'No se pudo registrar el pago en la bitácora. La operación fue cancelada.',
            0,
            $previous,
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'AUDIT_UNAVAILABLE';
    }
}
