<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\CatalogoStatusUnchangedException;

/**
 * Estado del tipo de diagnóstico (RN-D09).
 */
final class TipoDiagnosticoStateRule
{
    public static function assertStatusDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new CatalogoStatusUnchangedException;
        }
    }
}
