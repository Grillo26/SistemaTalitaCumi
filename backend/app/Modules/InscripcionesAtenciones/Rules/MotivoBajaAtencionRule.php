<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoBajaAtencionInvalidoException;

/**
 * RF-011: motivo cerrado + detalle libre; "Otro" exige detalle.
 */
final class MotivoBajaAtencionRule
{
    public static function assertValido(MotivoBajaAtencion $motivo, ?string $detalle): void
    {
        $detalle = $detalle === null ? null : trim($detalle);

        if ($motivo->requiereDetalle() && ($detalle === null || $detalle === '')) {
            throw new MotivoBajaAtencionInvalidoException(
                'El motivo "Otro" requiere un detalle.',
            );
        }

        if ($detalle !== null && mb_strlen($detalle) > 500) {
            throw new MotivoBajaAtencionInvalidoException(
                'El detalle del motivo no puede superar los 500 caracteres.',
            );
        }
    }
}
