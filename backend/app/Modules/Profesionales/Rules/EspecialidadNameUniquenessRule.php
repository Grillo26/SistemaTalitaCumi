<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\DuplicateEspecialidadNameException;

/**
 * Unicidad de nombre de especialidad (03/RF-E01 / 03/RN-06).
 *
 * Regla pura: la Action consulta el ID existente y se lo pasa.
 */
final class EspecialidadNameUniquenessRule
{
    public function assertAvailable(?int $existingSpecialtyIdWithSameName): void
    {
        if ($existingSpecialtyIdWithSameName !== null) {
            throw new DuplicateEspecialidadNameException;
        }
    }
}
