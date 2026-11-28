<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoConfiguracionIncompletaException extends PagosDomainException
{
    public function __construct(string $message = 'Falta un parámetro de Pagos y caja. No se puede registrar el pago.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'CONFIGURACION_INCOMPLETA';
    }
}
