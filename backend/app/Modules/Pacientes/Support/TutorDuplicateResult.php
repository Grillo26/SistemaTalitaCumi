<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Tutor;

/**
 * Resultado de duplicados de tutor.
 */
final readonly class TutorDuplicateResult
{
    public function __construct(
        public ?Tutor $documentTutor,
        public ?Tutor $namePhoneTutor,
    ) {}

    public function hasConflict(): bool
    {
        return $this->documentTutor !== null || $this->namePhoneTutor !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        $errors = [];

        if ($this->documentTutor !== null) {
            $errors['ci'] = ['Ya existe un tutor con ese documento.'];
        }

        if ($this->namePhoneTutor !== null) {
            $errors['telefono'] = ['Ya existe un tutor con el mismo nombre y teléfono principal.'];
        }

        return $errors;
    }
}
