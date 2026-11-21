<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Actions\Directorio\Concerns\ChangesProfesionalStatus;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;

/**
 * Desactivar profesional (03/RF-D07 / 03/RN-04, 03/RN-20).
 *
 * TODO 07/RT-09: cuando exista agenda, llamar a ProfessionalScheduleContract
 *::tieneAsignacionesFuturas() antes de desactivar. Hoy el contrato no existe.
 */
final class DeactivateProfesionalAction
{
    use ChangesProfesionalStatus;

    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function __invoke(
        Profesional $profesional,
        string $reason,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): Profesional {
        return $this->changeStatus($profesional, false, $reason, $expectedVersion, $actor, $ip);
    }
}
