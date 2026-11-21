<?php

namespace App\Modules\Pacientes\Rules;

/**
 * Alcance de edición de identidad de un tutor compartido (04.02/RF-009).
 *
 * Si un usuario solo ve una sucursal del tutor, no puede cambiar nombre,
 * documento ni parentesco de referencia porque afectaría a pacientes de
 * otras sucursales. El contacto sí puede editarse con el permiso específico.
 */
final class TutorIdentityEditScopeRule
{
    /**
     * @param  list<int>|null  $actorBranchIds  NULL = alcance global
     * @param  list<int>  $tutorBranchIds
     */
    public function canEditIdentity(?array $actorBranchIds, array $tutorBranchIds): bool
    {
        if ($actorBranchIds === null) {
            return true;
        }

        if ($actorBranchIds === [] || $tutorBranchIds === []) {
            return false;
        }

        return array_diff($tutorBranchIds, $actorBranchIds) === [];
    }
}
