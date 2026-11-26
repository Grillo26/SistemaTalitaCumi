<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionNoGestionableException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionRetiradaException;

/**
 * Transiciones admitidas en este submódulo: solo Activa -> Retirada.
 */
final class InscripcionStateTransitionRule
{
    public static function assertPuedeEditar(EstadoInscripcionSala $estado): void
    {
        self::assertActiva($estado);
    }

    public static function assertPuedeRenovar(EstadoInscripcionSala $estado): void
    {
        self::assertActiva($estado);
    }

    public static function assertPuedeRetirar(EstadoInscripcionSala $estado): void
    {
        self::assertActiva($estado);
    }

    private static function assertActiva(EstadoInscripcionSala $estado): void
    {
        if ($estado === EstadoInscripcionSala::ACTIVA) {
            return;
        }

        if ($estado === EstadoInscripcionSala::RETIRADA) {
            throw new InscripcionRetiradaException($estado);
        }

        throw new InscripcionNoGestionableException($estado);
    }
}
