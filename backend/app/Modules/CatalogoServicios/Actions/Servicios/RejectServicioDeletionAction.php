<?php

namespace App\Modules\CatalogoServicios\Actions\Servicios;

use App\Modules\CatalogoServicios\Exceptions\ServicioDeletionNotAllowedException;

/**
 * RF-C13 / RN-C08: los servicios no se eliminan.
 *
 * El chequeo real de tarifas/inscripciones/turnos se hará cuando existan
 * los submódulos 02 y los módulos 06/07, usando ServicioUsageContract.
 */
final class RejectServicioDeletionAction
{
    public function __invoke(int $servicioId): never
    {
        throw new ServicioDeletionNotAllowedException;
    }
}
