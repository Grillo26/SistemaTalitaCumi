<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\DuplicateTutorException;

/**
 * Unicidad de tutor (04.02/RF-003 / 04.02/RN-002).
 *
 * Documento único si existe; si no, nombre completo + teléfono principal.
 * La regla es pura: recibe IDs ya consultados.
 */
final class TutorUniquenessRule
{
    public function hasConflict(?int $existingDocumentId, ?int $existingNamePhoneId): bool
    {
        return $existingDocumentId !== null || $existingNamePhoneId !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(?int $existingDocumentId, ?int $existingNamePhoneId): array
    {
        $errors = [];

        if ($existingDocumentId !== null) {
            $errors['ci'] = ['Ya existe un tutor con ese documento.'];
        }

        if ($existingNamePhoneId !== null) {
            $errors['telefono'] = ['Ya existe un tutor con el mismo nombre y teléfono principal.'];
        }

        return $errors;
    }

    public function assertAvailable(?int $existingDocumentId, ?int $existingNamePhoneId): void
    {
        if ($this->hasConflict($existingDocumentId, $existingNamePhoneId)) {
            throw new DuplicateTutorException($this->errors($existingDocumentId, $existingNamePhoneId));
        }
    }
}
