<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\DuplicateTutorException;

/**
 * Unicidad de tutor (04.02/RF-003 / 04.02/RN-002).
 *
 * Documento único si existe; si no, nombre completo + teléfono principal.
 */
final class TutorUniquenessRule
{
    public function assertAvailable(
        ?int $existingDocumentId,
        ?int $existingNamePhoneId,
    ): void {
        $errors = [];

        if ($existingDocumentId !== null) {
            $errors['ci'] = ['Ya existe un tutor con ese documento.'];
        }

        if ($existingNamePhoneId !== null) {
            $errors['telefono'] = ['Ya existe un tutor con el mismo nombre y teléfono principal.'];
        }

        if ($errors !== []) {
            throw new DuplicateTutorException($errors);
        }
    }
}
