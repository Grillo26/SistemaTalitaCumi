<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Exceptions\TurnoCatalogoNotFoundException;
use App\Modules\Organizacion\Models\TurnoCatalogo;

final class GetTurnoCatalogoQuery
{
    public function __invoke(int $id): TurnoCatalogo
    {
        $turno = TurnoCatalogo::query()->find($id);

        if ($turno === null) {
            throw new TurnoCatalogoNotFoundException($id);
        }

        return $turno;
    }
}
