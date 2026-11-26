<?php

namespace App\Modules\CatalogoServicios\Contracts;

use LogicException;

/**
 * Stub seguro: no inventa datos ni devuelve false silenciosamente.
 */
final class StubTipoDiagnosticoUsageContract implements TipoDiagnosticoUsageContract
{
    public function hasDiagnosticosRegistrados(int $tipoDiagnosticoId): bool
    {
        throw new LogicException(
            'Módulo 06/11 — Diagnósticos registrados no implementados todavía.',
        );
    }
}
