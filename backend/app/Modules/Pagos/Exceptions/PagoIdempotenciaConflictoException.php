<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoIdempotenciaConflictoException extends PagosDomainException
{
    public function __construct(string $message = 'Esa clave de registro ya se usó con otro contenido. Iniciá un registro nuevo.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAGO_IDEMPOTENCIA_CONFLICTO';
    }
}
