<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteDeleteNotAllowedException;

/**
 * RN-010: los paquetes no se eliminan físicamente.
 */
final class RejectPaqueteDeletionAction
{
    public function __invoke(int $id): never
    {
        throw new PaqueteDeleteNotAllowedException;
    }
}
