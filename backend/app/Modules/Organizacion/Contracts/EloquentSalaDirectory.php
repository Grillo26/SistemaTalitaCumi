<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;
use App\Modules\Organizacion\Models\Sala;

final class EloquentSalaDirectory implements SalaDirectoryContract
{
    public function existe(int $salaId): bool
    {
        return Sala::query()->whereKey($salaId)->exists();
    }

    public function estaActiva(int $salaId): bool
    {
        return Sala::query()->whereKey($salaId)->where('activo', true)->exists();
    }

    /**
     * @param  list<int>  $salaIds
     * @return array<int, string>
     */
    public function nombres(array $salaIds): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($salaIds, static fn (mixed $id): bool => (int) $id > 0),
        )));

        if ($ids === []) {
            return [];
        }

        return Sala::query()
            ->whereIn('id', $ids)
            ->get(['id', 'nombre'])
            ->mapWithKeys(
                static fn (Sala $sala): array => [
                    (int) $sala->id => (string) $sala->nombre,
                ],
            )
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function nombresPorSucursal(int $sucursalId, bool $soloActivas = true): array
    {
        $query = Sala::query()
            ->where('sucursal_id', $sucursalId)
            ->orderBy('nombre');

        if ($soloActivas) {
            $query->where('activo', true);
        }

        return $query
            ->pluck('nombre', 'id')
            ->mapWithKeys(
                static fn (string $nombre, int|string $id): array => [
                    (int) $id => $nombre,
                ],
            )
            ->all();
    }

    public function perteneceASucursal(int $salaId, int $sucursalId): bool
    {
        return Sala::query()
            ->whereKey($salaId)
            ->where('sucursal_id', $sucursalId)
            ->exists();
    }

    public function asegurarActivaEnSucursal(int $salaId, int $sucursalId): void
    {
        $sala = Sala::query()->find($salaId);

        if ($sala === null) {
            throw new EspacioNotFoundException('sala', $salaId);
        }

        if ($sala->sucursal_id !== $sucursalId) {
            throw new EspacioSucursalIncorrectaException(
                'sala',
                $sala->id,
                $sucursalId,
                $sala->sucursal_id,
            );
        }

        if (! $sala->activo) {
            throw new EspacioInactivoException('sala', $sala->id, $sala->nombre);
        }
    }
}
