<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaConTarifasVigentesException;

/**
 * Estado de categoría de tarifa (RF-T02 / RN-T12).
 */
final class CategoriaTarifaStateRule
{
    public static function assertCanDeactivate(
        bool $hasActiveTariffs,
        string $categoriaNombre,
        int $activeTariffsCount,
    ): void {
        if ($hasActiveTariffs) {
            throw new CategoriaTarifaConTarifasVigentesException(
                $categoriaNombre,
                $activeTariffsCount,
            );
        }
    }
}
