<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\CantidadSesionesInvalidaException;

/**
 * RN-001: cantidad de sesiones entera >= 1.
 */
final class CantidadSesionesRule
{
    public static function assertValida(mixed $valor): int
    {
        if (is_string($valor) && ctype_digit($valor)) {
            $valor = (int) $valor;
        }

        if (! is_int($valor) || $valor < 1) {
            throw new CantidadSesionesInvalidaException;
        }

        return $valor;
    }
}
