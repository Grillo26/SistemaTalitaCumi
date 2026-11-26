<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoDiasInvalidosException;

/**
 * Días estimados del tipo de diagnóstico.
 */
final class TipoDiagnosticoDiasRule
{
    public static function normalizeAndValidate(mixed $dias): int
    {
        $value = (int) $dias;

        if ($value < 1) {
            throw new TipoDiagnosticoDiasInvalidosException;
        }

        return $value;
    }
}
