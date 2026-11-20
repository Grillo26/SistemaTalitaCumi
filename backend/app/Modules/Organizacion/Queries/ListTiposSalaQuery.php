<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Models\TipoSala;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de tipos de sala (RF-001/RF-008).
 */
final class ListTiposSalaQuery
{
    /**
     * @return Collection<int, TipoSala>
     */
    public function __invoke(?string $search, ?string $estado): Collection
    {
        return TipoSala::query()
            ->buscar($search)
            ->estado($estado)
            ->orderBy('nombre')
            ->get();
    }
}
