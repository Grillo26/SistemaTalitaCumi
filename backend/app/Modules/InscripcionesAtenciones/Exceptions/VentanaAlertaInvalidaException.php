<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class VentanaAlertaInvalidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $dias)
    {
        parent::__construct("La ventana de alerta debe estar entre 1 y 365 días (recibido: {$dias}).");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'VENTANA_ALERTA_INVALIDA';
    }
}
