<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionDeleteNotAllowedException;

/**
 * RN-009: las inscripciones no se eliminan físicamente.
 */
final class RejectInscripcionDeletionAction
{
    public function __invoke(int $id): never
    {
        throw new InscripcionDeleteNotAllowedException;
    }
}
