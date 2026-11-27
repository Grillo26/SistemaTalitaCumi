<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteNoGestionableException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteRetiradoException;

/**
 * Solo un paquete `activo` permite editar, renovar o dar de baja.
 */
final class PaqueteStateTransitionRule
{
    public static function assertPuedeEditar(EstadoPaqueteSesiones $estado): void
    {
        self::assertActivo($estado);
    }

    public static function assertPuedeRenovar(EstadoPaqueteSesiones $estado): void
    {
        self::assertActivo($estado);
    }

    public static function assertPuedeRetirar(EstadoPaqueteSesiones $estado): void
    {
        if ($estado === EstadoPaqueteSesiones::ACTIVO || $estado === EstadoPaqueteSesiones::EN_PAUSA) {
            return;
        }

        if ($estado === EstadoPaqueteSesiones::RETIRADO) {
            throw new PaqueteRetiradoException($estado);
        }

        throw new PaqueteNoGestionableException($estado);
    }

    private static function assertActivo(EstadoPaqueteSesiones $estado): void
    {
        if ($estado === EstadoPaqueteSesiones::ACTIVO) {
            return;
        }

        if ($estado === EstadoPaqueteSesiones::RETIRADO) {
            throw new PaqueteRetiradoException($estado);
        }

        throw new PaqueteNoGestionableException($estado);
    }
}
