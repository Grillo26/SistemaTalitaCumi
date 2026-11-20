<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Models\TurnoCatalogo;
use Illuminate\Database\Eloquent\Collection;

/**
 * Listado de turnos operativos (RF-003/RF-008).
 */
final class ListTurnosCatalogoQuery
{
    /**
     * @return Collection<int, TurnoCatalogo>
     */
    public function __invoke(?string $search, ?string $estado): Collection
    {
        return TurnoCatalogo::query()
            ->buscar($search)
            ->estado($estado)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }
}
