<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;

final class GetCategoriaTarifaQuery
{
    public function __invoke(int $categoriaTarifaId): CategoriaTarifa
    {
        $categoria = CategoriaTarifa::query()
            ->withCount([
                'tarifas as tarifas_vigentes_count' => static fn ($query) => $query->vigentes(),
                'tarifas as tarifas_total_count',
            ])
            ->find($categoriaTarifaId);

        if ($categoria === null) {
            throw new CategoriaTarifaNotFoundException($categoriaTarifaId);
        }

        return $categoria;
    }
}
