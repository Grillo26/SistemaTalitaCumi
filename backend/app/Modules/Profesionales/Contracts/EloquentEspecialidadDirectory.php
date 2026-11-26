<?php

namespace App\Modules\Profesionales\Contracts;

use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use Illuminate\Database\Eloquent\Builder;

/**
 * Implementación real del directorio de especialidades.
 */
final class EloquentEspecialidadDirectory implements EspecialidadDirectoryContract
{
    public function existe(int $especialidadId): bool
    {
        return Especialidad::query()->whereKey($especialidadId)->exists();
    }

    public function estaActiva(int $especialidadId): bool
    {
        return Especialidad::query()
            ->whereKey($especialidadId)
            ->where('activo', true)
            ->exists();
    }

    public function profesionalesActivosPorEspecialidad(
        int $especialidadId,
        int $sucursalId,
    ): int {
        return ProfesionalEspecialidad::query()
            ->where('especialidad_id', $especialidadId)
            ->where('activo', true)
            ->whereHas('profesional', static function (Builder $query): void {
                $query->where('activo', true);
            })
            ->whereHas('profesional.relacionesSucursal', static function (Builder $query) use ($sucursalId): void {
                $query->where('sucursal_id', $sucursalId)->where('activo', true);
            })
            ->count();
    }
}
