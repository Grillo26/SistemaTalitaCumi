<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaPaquete;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoBajaPaqueteInvalidoException;

/**
 * RF-014: motivo cerrado + detalle libre.
 */
final class MotivoBajaPaqueteRule
{
    public static function assertValido(MotivoBajaPaquete $motivo, ?string $detalle): void
    {
        if ($motivo->requiereDetalle() && trim((string) $detalle) === '') {
            throw new MotivoBajaPaqueteInvalidoException(
                'El motivo "Otro" requiere un detalle.',
            );
        }

        if ($detalle !== null && mb_strlen($detalle) > 500) {
            throw new MotivoBajaPaqueteInvalidoException(
                'El detalle del motivo no puede superar los 500 caracteres.',
            );
        }
    }
}
