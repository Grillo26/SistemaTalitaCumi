<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Models\TipoSala;

final class GetTipoSalaQuery
{
    public function __invoke(int $id): TipoSala
    {
        $tipoSala = TipoSala::query()->find($id);

        if ($tipoSala === null) {
            throw new TipoSalaNotFoundException($id);
        }

        return $tipoSala;
    }
}
