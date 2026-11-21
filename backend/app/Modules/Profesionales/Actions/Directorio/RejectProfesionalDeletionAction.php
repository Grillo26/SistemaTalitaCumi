<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Modules\Profesionales\Exceptions\DeletionNotAllowedException;
use App\Modules\Profesionales\Models\Profesional;

/**
 * Rechazo explícito de eliminación de profesional (03/RF-D08 / 03/RN-04).
 *
 * TODO 06/07/11: el chequeo real de historial se hará cuando existan turnos,
 * diagnósticos y atenciones. Mientras tanto la respuesta es siempre 409.
 */
final class RejectProfesionalDeletionAction
{
    public function __invoke(Profesional $profesional): never
    {
        throw new DeletionNotAllowedException(
            'el profesional',
            $profesional->nombre_completo,
            'PROFESIONAL_DELETE_NOT_ALLOWED',
        );
    }
}
