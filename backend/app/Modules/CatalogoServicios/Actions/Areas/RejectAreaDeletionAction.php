<?php

namespace App\Modules\CatalogoServicios\Actions\Areas;

use App\Modules\CatalogoServicios\Exceptions\AreaDeletionNotAllowedException;

/**
 * RF-C13 / RN-C08: las áreas no se eliminan.
 */
final class RejectAreaDeletionAction
{
    public function __invoke(int $areaId): never
    {
        throw new AreaDeletionNotAllowedException;
    }
}
