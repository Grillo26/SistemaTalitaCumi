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
