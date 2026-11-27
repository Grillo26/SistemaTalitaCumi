<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionResumen;

/**
 * Resumen de asistencia de la inscripción, en endpoint separado de la ficha.
 */
final class GetAsistenciaInscripcionQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
        private readonly AsistenciaInscripcionContract $asistencias,
    ) {}

    public function __invoke(int $id, User $actor): AsistenciaInscripcionResumen
    {
        ($this->detalle)($id, $actor);

        return $this->asistencias->resumen($id);
    }
}
