<?php

namespace App\Modules\Profesionales\Contracts;

use App\Modules\Profesionales\Exceptions\ProfesionalInactivoException;
use App\Modules\Profesionales\Exceptions\ProfesionalNotFoundException;
use App\Modules\Profesionales\Models\Profesional;

/**
 * Implementación real del directorio de profesionales.
 */
final class EloquentProfessionalDirectory implements ProfessionalDirectoryContract
{
    public function existe(int $profesionalId): bool
    {
        return Profesional::query()->whereKey($profesionalId)->exists();
    }

    public function estaActivo(int $profesionalId): bool
    {
        return Profesional::query()
            ->whereKey($profesionalId)
            ->where('activo', true)
            ->exists();
    }

    public function asegurarActivo(int $profesionalId): void
    {
        $profesional = Profesional::query()
            ->select(['id', 'activo'])
            ->find($profesionalId);

        if ($profesional === null) {
            throw new ProfesionalNotFoundException($profesionalId);
        }

        if (! $profesional->activo) {
            throw new ProfesionalInactivoException($profesionalId);
        }
    }
}
