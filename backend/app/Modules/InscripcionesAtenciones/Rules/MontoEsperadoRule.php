<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\MontoEsperadoInvalidoException;

/**
 * Dinero en Bs como string decimal exacto; nunca float.
 */
final class MontoEsperadoRule
{
    public static function assertValido(string $monto): void
    {
        if (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $monto)) {
            throw new MontoEsperadoInvalidoException($monto);
        }

        if (bccomp($monto, '0', 2) < 0) {
            throw new MontoEsperadoInvalidoException($monto);
        }
    }
}
