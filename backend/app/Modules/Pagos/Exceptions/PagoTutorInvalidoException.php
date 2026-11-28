<?php

namespace App\Modules\Pagos\Exceptions;

final class PagoTutorInvalidoException extends PagosDomainException
{
    public function __construct(string $message = 'El tutor debe estar activo y asociado al paciente de la entidad.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PAGO_TUTOR_INVALIDO';
    }
}
