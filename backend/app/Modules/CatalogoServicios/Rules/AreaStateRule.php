<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\AreaConServiciosActivosException;

/**
 * Estado del área (RN-C07 / RF-C02).
 *
 * Recibe el conteo ya consultado; no toca BD.
 */
final class AreaStateRule
{
    public static function assertCanDeactivate(
        bool $hasActiveServices,
        string $areaName,
        int $activeServicesCount,
    ): void {
        if ($hasActiveServices) {
            throw new AreaConServiciosActivosException($areaName, $activeServicesCount);
        }
    }
}
