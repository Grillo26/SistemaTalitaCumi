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

    /**
     * @param  list<int>  $sucursalIds
     * @return array<int, BranchResumen>
     */
    public function resumenes(array $sucursalIds): array
    {
        $ids = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($sucursalIds, static fn (mixed $id): bool => (int) $id > 0),
        )));

        if ($ids === []) {
            return [];
        }

        $resumenes = [];

        foreach (Branch::query()->whereIn('id', $ids)->get() as $branch) {
            $resumen = BranchResumen::fromBranch($branch);
            $resumenes[$resumen->id] = $resumen;
        }

        return $resumenes;
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
