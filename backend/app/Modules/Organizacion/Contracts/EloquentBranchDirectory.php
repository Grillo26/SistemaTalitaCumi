<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchOperationalStatusRule;

/**
 * Implementación real del directorio de sucursales.
 */
final class EloquentBranchDirectory implements BranchDirectoryContract
{
    public function existe(int $sucursalId): bool
    {
        return Branch::query()->whereKey($sucursalId)->exists();
    }

    public function estaActiva(int $sucursalId): bool
    {
        return Branch::query()
            ->whereKey($sucursalId)
            ->where('activo', true)
            ->exists();
    }

    public function asegurarActiva(int $sucursalId): void
    {
        $branch = Branch::query()
            ->select(['id', 'nombre', 'activo'])
            ->find($sucursalId);

        if ($branch === null) {
            throw new BranchNotFoundException($sucursalId);
        }

        BranchOperationalStatusRule::assertActive($branch->activo, $branch->id, $branch->nombre);
    }
}
