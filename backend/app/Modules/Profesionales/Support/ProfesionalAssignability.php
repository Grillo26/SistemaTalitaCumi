<?php

namespace App\Modules\Profesionales\Support;

use App\Modules\Profesionales\Enums\MotivoNoAsignable;
use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Rules\ProfesionalAssignabilityRule;

/**
 * Cálculo de asignabilidad visible en el directorio (03/RN-03, 03/RN-07, 03/RN-09).
 *
 * Para un usuario con alcance limitado solo cuentan sus sucursales
 * accesibles; nunca se revela el estado de sucursales ajenas.
 */
final class ProfesionalAssignability
{
    /**
     * @param  list<int>|null  $accessibleBranchIds  NULL = alcance global
     * @return array{asignable: bool, motivos: list<array{codigo: string, mensaje: string}>}
     */
    public function for(Profesional $profesional, ?array $accessibleBranchIds = null): array
    {
        $profesional->loadMissing([
            'relacionesEspecialidad.especialidad',
            'relacionesSucursal.sucursal',
        ]);

        $principal = $profesional->relacionesEspecialidad
            ->first(static fn ($relacion): bool => (bool) $relacion->activo
                && $relacion->tipo === TipoEspecialidad::PRINCIPAL);

        $hasActivePrincipal = $principal !== null;
        $principalSpecialtyActive = $principal?->especialidad?->activo === true;

        $hasActiveBranch = false;
        $hasInactiveBranch = false;

        foreach ($profesional->relacionesSucursal as $relacion) {
            if (! $relacion->activo || $relacion->sucursal === null) {
                continue;
            }

            if ($accessibleBranchIds !== null
                && ! in_array((int) $relacion->sucursal_id, $accessibleBranchIds, true)) {
                continue;
            }

            if ($relacion->sucursal->activo) {
                $hasActiveBranch = true;
            } else {
                $hasInactiveBranch = true;
            }
        }

        /** @var list<MotivoNoAsignable> $motivos */
        $motivos = ProfesionalAssignabilityRule::reasons(
            professionalActive: (bool) $profesional->activo,
            hasActivePrincipal: $hasActivePrincipal,
            principalSpecialtyActive: $principalSpecialtyActive,
            hasActiveBranch: $hasActiveBranch,
            hasInactiveBranch: $hasInactiveBranch,
        );

        return [
            'asignable' => $motivos === [],
            'motivos' => array_map(
                static fn (MotivoNoAsignable $motivo): array => [
                    'codigo' => $motivo->value,
                    'mensaje' => $motivo->label(),
                ],
                $motivos,
            ),
        ];
    }
}
