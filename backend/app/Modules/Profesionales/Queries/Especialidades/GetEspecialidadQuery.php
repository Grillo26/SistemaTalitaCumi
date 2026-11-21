<?php

namespace App\Modules\Profesionales\Queries\Especialidades;

use App\Modules\Profesionales\Exceptions\EspecialidadNotFoundException;
use App\Modules\Profesionales\Models\Especialidad;

/**
 * Ficha de especialidad (03/RF-E03).
 */
final class GetEspecialidadQuery
{
    public function __invoke(int $especialidadId): Especialidad
    {
        $especialidad = Especialidad::query()
            ->withCount('profesionalesActivos')
            ->find($especialidadId);

        if ($especialidad === null) {
            throw new EspecialidadNotFoundException($especialidadId);
        }

        return $especialidad;
    }
}
