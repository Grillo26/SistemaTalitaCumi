<?php

namespace App\Modules\Profesionales\Actions\Especialidades;

use App\Modules\Profesionales\Exceptions\DeletionNotAllowedException;
use App\Modules\Profesionales\Exceptions\EspecialidadNotFoundException;
use App\Modules\Profesionales\Models\Especialidad;

/**
 * Rechazo explícito de eliminación de especialidad (03/RF-E11 / 03/RN-04).
 */
final class RejectEspecialidadDeletionAction
{
    public function __invoke(int $especialidadId): never
    {
        $especialidad = Especialidad::query()->find($especialidadId);

        if ($especialidad === null) {
            throw new EspecialidadNotFoundException($especialidadId);
        }

        throw new DeletionNotAllowedException(
            'la especialidad',
            $especialidad->nombre,
            'ESPECIALIDAD_DELETE_NOT_ALLOWED',
        );
    }
}
