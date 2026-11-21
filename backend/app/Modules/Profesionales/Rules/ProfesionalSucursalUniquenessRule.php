<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\ProfesionalSucursalDuplicatedException;

/**
 * Unicidad de la relación profesional–sucursal (03/RF-E07).
 */
final class ProfesionalSucursalUniquenessRule
{
    public function assertCanAssign(bool $activeRelationExists, bool $inactiveRelationExists): void
    {
        if ($activeRelationExists) {
            throw new ProfesionalSucursalDuplicatedException(true);
        }

        if ($inactiveRelationExists) {
            throw new ProfesionalSucursalDuplicatedException(false);
        }
    }
}
