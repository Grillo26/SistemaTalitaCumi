<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Enums\MotivoNoAsignable;

/**
 * Evaluación pura de asignabilidad (03/RN-03, 03/RN-07, 03/RN-09).
 */
final class ProfesionalAssignabilityRule
{
    /**
     * @return list<MotivoNoAsignable>
     */
    public static function reasons(
        bool $professionalActive,
        bool $hasActivePrincipal,
        bool $principalSpecialtyActive,
        bool $hasActiveBranch,
        bool $hasInactiveBranch,
    ): array {
        $reasons = [];

        if (! $professionalActive) {
            $reasons[] = MotivoNoAsignable::PROFESIONAL_INACTIVO;
        }

        if (! $hasActivePrincipal) {
            $reasons[] = MotivoNoAsignable::SIN_ESPECIALIDAD_ACTIVA;
        } elseif (! $principalSpecialtyActive) {
            $reasons[] = MotivoNoAsignable::ESPECIALIDAD_INACTIVA;
        }

        if (! $hasActiveBranch) {
            $reasons[] = $hasInactiveBranch
                ? MotivoNoAsignable::SUCURSAL_INACTIVA
                : MotivoNoAsignable::SIN_SUCURSAL_ACTIVA;
        }

        return array_values(array_reduce(
            $reasons,
            static function (array $carry, MotivoNoAsignable $reason): array {
                $carry[$reason->value] = $reason;

                return $carry;
            },
            [],
        ));
    }
}
