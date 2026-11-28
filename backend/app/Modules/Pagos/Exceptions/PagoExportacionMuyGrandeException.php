<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoExportacionMuyGrandeException extends PagosDomainException
{
    public function __construct(string $message = 'La exportación supera el máximo permitido. Reducí los filtros.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'EXPORT_TOO_LARGE';
    }
}
