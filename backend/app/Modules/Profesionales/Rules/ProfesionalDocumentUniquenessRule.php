<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\DuplicateProfesionalDocumentException;

/**
 * Unicidad de documento del profesional (03/RF-D02 / 03/RN-01).
 *
 * Regla pura: la Action consulta el ID existente y se lo pasa.
 */
final class ProfesionalDocumentUniquenessRule
{
    public function assertAvailable(string $ci, ?int $existingProfessionalIdWithSameDocument): void
    {
        if ($existingProfessionalIdWithSameDocument !== null) {
            throw new DuplicateProfesionalDocumentException;
        }
    }
}
