<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\TarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\Tarifa;

final class GetTarifaQuery
{
    public function __invoke(int $tarifaId): Tarifa
    {
        $tarifa = Tarifa::query()
            ->with(['servicio.area', 'categoria', 'sucursal'])
            ->find($tarifaId);

        if ($tarifa === null) {
            throw new TarifaNotFoundException($tarifaId);
        }

        return $tarifa;
    }
}
