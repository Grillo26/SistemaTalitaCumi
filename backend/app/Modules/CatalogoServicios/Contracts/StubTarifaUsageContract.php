<?php

namespace App\Modules\CatalogoServicios\Contracts;

use LogicException;

/**
 * Stub seguro: no inventa datos ni devuelve false silenciosamente.
 */
final class StubTarifaUsageContract implements TarifaUsageContract
{
    public function hasInscripcionesOrPagos(int $tarifaId): bool
    {
        throw new LogicException(
            'Módulo 06/08 — Inscripciones y pagos no implementados todavía.',
        );
    }
}
