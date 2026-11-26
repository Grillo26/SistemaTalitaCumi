<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\AreaInactivaException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoStatusUnchangedException;

/**
 * Estado del servicio (RN-C06 / RN-C07).
 */
final class ServicioStateRule
{
    public static function assertAreaActive(bool $areaActiva, string $areaNombre): void
    {
        if (! $areaActiva) {
            throw new AreaInactivaException($areaNombre);
        }
    }

    public static function assertStatusDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new CatalogoStatusUnchangedException;
        }
    }
}
