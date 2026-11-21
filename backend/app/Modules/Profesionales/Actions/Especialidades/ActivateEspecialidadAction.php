<?php

namespace App\Modules\Profesionales\Actions\Especialidades;

use App\Modules\Profesionales\Actions\Especialidades\Concerns\ChangesEspecialidadStatus;
use App\Modules\Profesionales\Models\Especialidad;

/**
 * Activar especialidad (03/RF-E02 / 03/RN-20).
 */
final class ActivateEspecialidadAction
{
    use ChangesEspecialidadStatus;

    public function __invoke(
        int $especialidadId,
        string $reason,
        int $expectedVersion,
        ?int $actorId,
        ?string $ip = null,
    ): Especialidad {
        return $this->changeStatus($especialidadId, true, $reason, $expectedVersion, $actorId, $ip);
    }
}
