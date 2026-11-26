<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\MontoEsperadoInvalidoException;

/**
 * RN-008 / RF-008 / RT-004: el backend calcula el total.
 */
final class MontoPaqueteRule
{
    public static function calcular(string $montoSesion, int $cantidadSesiones): string
    {
        MontoEsperadoRule::assertValido($montoSesion);

        $total = bcmul($montoSesion, (string) $cantidadSesiones, 2);

        if (bccomp($total, '0', 2) < 0) {
            throw new MontoEsperadoInvalidoException($total);
        }

        MontoEsperadoRule::assertValido($total);

        return $total;
    }
}
