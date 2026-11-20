<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de parámetros con búsqueda y filtro por categoría (RF-001/RF-002).
 *
 * El catálogo es acotado (RNF-001: hasta 200 parámetros), por eso no se
 * pagina: la UI agrupa por categoría.
 */
final class ListParametersQuery
{
    /**
     * @return Collection<int, Parametro>
     */
    public function __invoke(?string $search, ?string $categoria, bool $onlyVisible): Collection
    {
        return Parametro::query()
            ->buscar($search)
            ->categoria($categoria)
            ->visiblesRecepcion($onlyVisible)
            ->orderBy('clave')
            ->get()
            ->sortBy(fn (Parametro $parametro): string => sprintf(
                '%03d-%s',
                $parametro->categoria->order(),
                $parametro->clave,
            ))
            ->values();
    }
}
