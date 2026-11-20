<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Exceptions\ParameterAccessDeniedException;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Models\Parametro;

/**
 * Ficha de un parámetro (CU-01).
 *
 * Recepción solo puede abrir parámetros visibles para su rol; el filtro se
 * aplica en backend, no solo en la UI (RNF-004 / RT-007).
 */
final class GetParameterQuery
{
    public function __invoke(int $id, bool $onlyVisible): Parametro
    {
        $parametro = Parametro::query()->find($id);

        if ($parametro === null) {
            throw new ParameterNotFoundException($id);
        }

        if ($onlyVisible && ! $parametro->visible_recepcion) {
            throw new ParameterAccessDeniedException;
        }

        return $parametro;
    }
}
