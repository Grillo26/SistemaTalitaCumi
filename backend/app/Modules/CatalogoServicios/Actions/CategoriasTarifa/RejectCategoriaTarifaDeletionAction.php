<?php

namespace App\Modules\CatalogoServicios\Actions\CategoriasTarifa;

use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaDeletionNotAllowedException;

/**
 * Las categorías no se eliminan (RN-T09 / RF-T13).
 */
final class RejectCategoriaTarifaDeletionAction
{
    public function __invoke(int $categoriaTarifaId): never
    {
        throw new CategoriaTarifaDeletionNotAllowedException;
    }
}
