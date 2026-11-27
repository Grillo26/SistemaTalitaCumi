<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Modules\InscripcionesAtenciones\Exceptions\AtencionDeleteNotAllowedException;

/**
 * Las atenciones nunca se eliminan físicamente.
 */
final class RejectAtencionDeletionAction
{
    public function __invoke(int $id): never
    {
        throw new AtencionDeleteNotAllowedException($id);
    }
}
