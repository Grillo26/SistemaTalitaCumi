<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Tutor;

/**
 * Detección de duplicados de tutor (04.02/RF-003).
 */
final class TutorDuplicateDetector
{
    public function detect(
        string $name,
        ?string $document,
        string $phone,
        ?int $excludeTutorId = null,
    ): TutorDuplicateResult {
        $nameKey = PacienteNormalizer::key($name);
        $documentKey = PacienteNormalizer::documentKey($document);
        $phoneKey = PacienteNormalizer::phoneKey($phone);

        $documentTutor = null;

        if ($documentKey !== null) {
            $documentTutor = Tutor::query()
                ->when($excludeTutorId !== null, fn ($query) => $query->whereKeyNot($excludeTutorId))
                ->where('documento_normalizado', $documentKey)
                ->with('parentescoCatalogo')
                ->first();
        }

        $namePhoneTutor = Tutor::query()
            ->when($excludeTutorId !== null, fn ($query) => $query->whereKeyNot($excludeTutorId))
            ->where('nombre_completo_normalizado', $nameKey)
            ->where('telefono_normalizado', $phoneKey)
            ->with('parentescoCatalogo')
            ->first();

        return new TutorDuplicateResult($documentTutor, $namePhoneTutor);
    }
}
