<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResumen;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaNoVigenteException;

/**
 * RN-011: la tarifa resuelta debe estar vigente a la fecha de inicio.
 */
final class TarifaVigenteRule
{
    public static function assertVigente(?TarifaAplicableResumen $tarifa, string $fechaInicio): void
    {
        if ($tarifa === null) {
            throw new TarifaNoVigenteException;
        }

        if ($tarifa->vigenteDesde > $fechaInicio) {
            throw new TarifaNoVigenteException;
        }

        if ($tarifa->vigenteHasta !== null && $tarifa->vigenteHasta < $fechaInicio) {
            throw new TarifaNoVigenteException;
        }

        MontoEsperadoRule::assertValido($tarifa->monto);
    }
}
