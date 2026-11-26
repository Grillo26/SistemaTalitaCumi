<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\DuracionSesionInvalidaException;

/**
 * RN-003: duración entera > 0.
 */
final class DuracionSesionRule
{
    public static function assertValida(mixed $valor): int
    {
        if (is_string($valor) && ctype_digit($valor)) {
            $valor = (int) $valor;
        }

        if (! is_int($valor) || $valor < 1) {
            throw new DuracionSesionInvalidaException;
        }

        return $valor;
    }
}
