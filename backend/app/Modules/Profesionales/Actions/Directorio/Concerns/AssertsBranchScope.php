<?php

namespace App\Modules\Profesionales\Actions\Directorio\Concerns;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Profesionales\Support\ProfesionalesPermission;

/**
 * Verifica que la sucursal esté dentro del alcance del usuario (03/RN-18).
 */
trait AssertsBranchScope
{
    protected function assertBranchInScope(
        UserBranchAccessContract $access,
        User $actor,
        int $sucursalId,
    ): void {
        if ($actor->can(ProfesionalesPermission::GLOBAL_SCOPE)) {
            return;
        }

        if (! $access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }
    }
}
