<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Exceptions\ParameterAccessDeniedException;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Historial de cambios de un parámetro (FP-04 / RNF-013).
 */
final class GetParameterHistoryQuery
{
    public function __invoke(int $id, bool $onlyVisible, int $page, int $perPage): LengthAwarePaginator
    {
        $parametro = Parametro::query()->find($id);

        if ($parametro === null) {
            throw new ParameterNotFoundException($id);
        }

        if ($onlyVisible && ! $parametro->visible_recepcion) {
            throw new ParameterAccessDeniedException;
        }

        return BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', 'parametro')
            ->where('entidad_id', $id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(max(1, min($perPage, 50)), ['*'], 'page', max(1, $page))
            ->withQueryString();
    }
}
