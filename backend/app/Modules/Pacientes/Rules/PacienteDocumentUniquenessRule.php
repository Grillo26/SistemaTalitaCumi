<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\DuplicatePacienteDocumentException;

/**
 * Unicidad de documento del paciente (04.01/RF-003 / 04.01/RN-002).
 *
 * Regla pura: la Action consulta el ID existente y se lo pasa.
 */
final class PacienteDocumentUniquenessRule
{
    public function isAvailable(?int $existingPacienteId): bool
    {
        return $existingPacienteId === null;
    }

    public function assertAvailable(?int $existingPacienteId): void
    {
        if (! $this->isAvailable($existingPacienteId)) {
            throw new DuplicatePacienteDocumentException;
        }
    }
}
