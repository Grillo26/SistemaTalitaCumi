<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\AreaNotFoundException;
use App\Modules\CatalogoServicios\Models\Area;

final class GetAreaQuery
{
    public function __invoke(int $areaId): Area
    {
        $area = Area::query()
            ->withCount([
                'servicios as servicios_activos_count' => static fn ($query) => $query->where('activo', true),
                'servicios as servicios_total_count',
            ])
            ->find($areaId);

        if ($area === null) {
            throw new AreaNotFoundException($areaId);
        }

        return $area;
    }
}
