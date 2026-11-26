<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\ObservacionesInvalidasException;

/**
 * Observaciones administrativas del paquete con máximo 500 caracteres.
 */
final class PaqueteObservacionesRule
{
    public static function assertValidas(?string $observaciones): void
    {
        if ($observaciones !== null && mb_strlen($observaciones) > 500) {
            throw new ObservacionesInvalidasException;
        }
    }
}
