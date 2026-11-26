<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\Servicio;

final class GetServicioQuery
{
    public function __invoke(int $servicioId): Servicio
    {
        $servicio = Servicio::query()
            ->with(['area', 'tipoServicio'])
            ->find($servicioId);

        if ($servicio === null) {
            throw new ServicioNotFoundException($servicioId);
        }

        return $servicio;
    }
}
