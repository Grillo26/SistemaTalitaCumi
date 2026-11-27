<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBaja;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoBajaInvalidoException;

/**
 * RF-011: motivo cerrado + detalle libre, con "otro" exigiendo detalle.
 */
final class MotivoBajaRule
{
    public static function assertValido(MotivoBaja $motivo, ?string $detalle): void
    {
        if ($motivo->requiereDetalle() && trim((string) $detalle) === '') {
            throw new MotivoBajaInvalidoException(
                'El motivo "Otro" requiere un detalle.',
            );
        }

        if ($detalle !== null && mb_strlen($detalle) > 500) {
            throw new MotivoBajaInvalidoException(
                'El detalle del motivo no puede superar los 500 caracteres.',
            );
        }
    }
}
