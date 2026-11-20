<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Exceptions\DeletionNotAllowedException;
use App\Modules\Organizacion\Exceptions\TurnoCatalogoNotFoundException;
use App\Modules\Organizacion\Models\TurnoCatalogo;

/**
 * Rechazo explícito de eliminación de turno operativo.
 */
final class RejectTurnoCatalogoDeletionAction
{
    public function __invoke(int $id): never
    {
        $turno = TurnoCatalogo::query()->find($id);

        if ($turno === null) {
            throw new TurnoCatalogoNotFoundException($id);
        }

        throw new DeletionNotAllowedException(
            'el turno',
            $turno->nombre,
            'CATALOG_DELETE_NOT_ALLOWED',
        );
    }
}
