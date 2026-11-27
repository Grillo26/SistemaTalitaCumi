<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\VentanaAlertaInvalidaException;

/**
 * [Vigencia y Renovación] RF-003 / RN-007.
 *
 * La regla solo valida el rango; el valor inicial no vive acá.
 */
final class VentanaAlertaRule
{
    public const MINIMO = 1;

    public const MAXIMO = 365;

    public static function assertValida(int $dias): void
    {
        if ($dias < self::MINIMO || $dias > self::MAXIMO) {
            throw new VentanaAlertaInvalidaException($dias);
        }
    }
}
