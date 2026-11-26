<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\CatalogoStatusUnchangedException;

/**
 * Estado de un tipo de servicio (RN-C09).
 */
final class TipoServicioStateRule
{
    public static function assertStatusDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new CatalogoStatusUnchangedException;
        }
    }
}
