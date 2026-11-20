<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Exceptions\DeletionNotAllowedException;
use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Models\TipoSala;

/**
 * Rechazo explícito de eliminación de tipo de sala.
 */
final class RejectTipoSalaDeletionAction
{
    public function __invoke(int $id): never
    {
        $tipoSala = TipoSala::query()->find($id);

        if ($tipoSala === null) {
            throw new TipoSalaNotFoundException($id);
        }

        throw new DeletionNotAllowedException(
            'el tipo de sala',
            $tipoSala->nombre,
            'CATALOG_DELETE_NOT_ALLOWED',
        );
    }
}
