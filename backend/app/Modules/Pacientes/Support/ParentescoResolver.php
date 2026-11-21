<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Exceptions\TutorParentescoInvalidException;
use App\Modules\Pacientes\Exceptions\TutorParentescoRequiredException;
use App\Modules\Pacientes\Models\Parentesco;

/**
 * Resolución de parentesco por ID o por nombre legacy (04.02/RN-005).
 */
final class ParentescoResolver
{
    public function resolve(?int $parentescoId, ?string $nombre): Parentesco
    {
        if ($parentescoId !== null) {
            $parentesco = Parentesco::query()
                ->whereKey($parentescoId)
                ->where('activo', true)
                ->first();

            if ($parentesco === null) {
                throw new TutorParentescoInvalidException;
            }

            return $parentesco;
        }

        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            throw new TutorParentescoRequiredException;
        }

        $parentesco = Parentesco::query()
            ->where('nombre_normalizado', PacienteNormalizer::key($nombre))
            ->where('activo', true)
            ->first();

        if ($parentesco === null) {
            throw new TutorParentescoInvalidException;
        }

        return $parentesco;
    }
}
