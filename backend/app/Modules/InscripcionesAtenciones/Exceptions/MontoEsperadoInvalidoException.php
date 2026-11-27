<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class MontoEsperadoInvalidoException extends InscripcionesAtencionesDomainException
{
    public function __construct(string $monto)
    {
        parent::__construct("El monto esperado resuelto no es válido: {$monto}.");
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'MONTO_ESPERADO_INVALIDO';
    }
}
