<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;
use App\Modules\Organizacion\Models\Ambiente;

final class EloquentAmbienteDirectory implements AmbienteDirectoryContract
{
    public function existe(int $ambienteId): bool
    {
        return Ambiente::query()->whereKey($ambienteId)->exists();
    }

    public function estaActiva(int $ambienteId): bool
    {
        return Ambiente::query()->whereKey($ambienteId)->where('activo', true)->exists();
    }

    public function perteneceASucursal(int $ambienteId, int $sucursalId): bool
    {
        return Ambiente::query()
            ->whereKey($ambienteId)
            ->where('sucursal_id', $sucursalId)
            ->exists();
    }

    public function asegurarActivaEnSucursal(int $ambienteId, int $sucursalId): void
    {
        $ambiente = Ambiente::query()->find($ambienteId);

        if ($ambiente === null) {
            throw new EspacioNotFoundException('ambiente', $ambienteId);
        }

        if ($ambiente->sucursal_id !== $sucursalId) {
            throw new EspacioSucursalIncorrectaException(
                'ambiente',
                $ambiente->id,
                $sucursalId,
                $ambiente->sucursal_id,
            );
        }

        if (! $ambiente->activo) {
            throw new EspacioInactivoException('ambiente', $ambiente->id, $ambiente->nombre);
        }
    }
}
