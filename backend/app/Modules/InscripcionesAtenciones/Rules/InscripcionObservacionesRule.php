<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\ObservacionesInvalidasException;

/**
 * RF-006: observaciones de texto libre con máximo 500 caracteres.
 */
final class InscripcionObservacionesRule
{
    public static function assertValidas(?string $observaciones): void
    {
        if ($observaciones !== null && mb_strlen($observaciones) > 500) {
            throw new ObservacionesInvalidasException;
        }
    }
}
