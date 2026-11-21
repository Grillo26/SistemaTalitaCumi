<?php

namespace App\Modules\Profesionales\Actions\Especialidades;

use App\Modules\Profesionales\Actions\Especialidades\Concerns\ChangesEspecialidadStatus;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use App\Modules\Profesionales\Rules\EspecialidadDeactivationRule;

/**
 * Desactivar especialidad (03/RF-E02 / 03/RN-09).
 *
 * No se permite si hay profesionales activos asociados. TODO 07/RT-09:
 * cuando exista agenda, sumar la verificación de asignaciones futuras.
 */
final class DeactivateEspecialidadAction
{
    use ChangesEspecialidadStatus;

    public function __construct(private readonly EspecialidadDeactivationRule $rule) {}

    public function __invoke(
        int $especialidadId,
        string $reason,
        int $expectedVersion,
        ?int $actorId,
        ?string $ip = null,
    ): Especialidad {
        return $this->changeStatus(
            $especialidadId,
            false,
            $reason,
            $expectedVersion,
            $actorId,
            $ip,
            function (Especialidad $especialidad): void {
                $activeProfessionals = ProfesionalEspecialidad::query()
                    ->where('especialidad_id', $especialidad->id)
                    ->where('activo', true)
                    ->whereHas('profesional', static fn ($query) => $query->where('activo', true))
                    ->count();

                $this->rule->assertCanDeactivate($activeProfessionals, $especialidad->nombre);
            },
        );
    }
}
