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

    /**
     * @param  list<int>  $profesionalIds
     * @return array<int, string>
     */
    public function nombres(array $profesionalIds): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($profesionalIds, static fn (mixed $id): bool => (int) $id > 0),
        )));

        if ($ids === []) {
            return [];
        }

        return Profesional::query()
            ->whereIn('id', $ids)
            ->get(['id', 'nombre_completo'])
            ->mapWithKeys(
                static fn (Profesional $profesional): array => [
                    (int) $profesional->id => (string) $profesional->nombre_completo,
                ],
            )
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function nombresPorSucursal(int $sucursalId, bool $soloActivos = true): array
    {
        $query = Profesional::query()
            ->whereHas(
                'relacionesSucursal',
                static fn ($relacion) => $relacion
                    ->where('sucursal_id', $sucursalId)
                    ->where('activo', true),
            )
            ->orderBy('nombre_completo');

        if ($soloActivos) {
            $query->where('activo', true);
        }

        return $query
            ->pluck('nombre_completo', 'id')
            ->mapWithKeys(
                static fn (string $nombre, int|string $id): array => [
                    (int) $id => $nombre,
                ],
            )
            ->all();
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
