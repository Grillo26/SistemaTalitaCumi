<?php

namespace App\Modules\CatalogoServicios\Actions\Tarifas;

use App\Modules\CatalogoServicios\Exceptions\TarifaDeletionNotAllowedException;

/**
 * Las tarifas no se eliminan (RF-T13 / RN-T09).
 *
 * El chequeo real de inscripciones/pagos se hará con TarifaUsageContract
 * cuando existan los módulos 06/08.
 */
final class RejectTarifaDeletionAction
{
    public function __invoke(int $tarifaId): never
    {
        throw new TarifaDeletionNotAllowedException;
    }
}
