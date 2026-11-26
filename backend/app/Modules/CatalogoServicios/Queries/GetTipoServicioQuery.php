<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\TipoServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\TipoServicio;

final class GetTipoServicioQuery
{
    public function __invoke(int $tipoServicioId): TipoServicio
    {
        $tipo = TipoServicio::query()
            ->withCount('servicios as servicios_total_count')
            ->find($tipoServicioId);

        if ($tipo === null) {
            throw new TipoServicioNotFoundException($tipoServicioId);
        }

        return $tipo;
    }
}
