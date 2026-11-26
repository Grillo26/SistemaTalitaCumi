<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionNoGestionableException;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionRetiradaException;

/**
 * Transiciones admitidas: Activa -> Retirada. Sin reactivación ni borrado.
 */
final class AtencionStateTransitionRule
{
    public static function assertPuedeEditar(EstadoAtencion $estado): void
    {
        self::assertActiva($estado);
    }

    public static function assertPuedeRetirar(EstadoAtencion $estado): void
    {
        self::assertActiva($estado);
    }

    private static function assertActiva(EstadoAtencion $estado): void
    {
        if ($estado === EstadoAtencion::ACTIVA) {
            return;
        }

        if ($estado === EstadoAtencion::RETIRADA) {
            throw new AtencionRetiradaException($estado);
        }

        throw new AtencionNoGestionableException($estado);
    }
}
