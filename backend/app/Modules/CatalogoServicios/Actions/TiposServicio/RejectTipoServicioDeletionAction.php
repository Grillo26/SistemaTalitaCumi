<?php

namespace App\Modules\CatalogoServicios\Actions\TiposServicio;

use App\Modules\CatalogoServicios\Exceptions\TipoServicioDeletionNotAllowedException;

/**
 * Los tipos de servicio no se eliminan.
 */
final class RejectTipoServicioDeletionAction
{
    public function __invoke(int $tipoServicioId): never
    {
        throw new TipoServicioDeletionNotAllowedException;
    }
}
