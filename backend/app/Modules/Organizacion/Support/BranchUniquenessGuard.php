<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Exceptions\DuplicateBranchFieldException;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Database\QueryException;

/**
 * Comprobación de unicidad contra la base de datos.
 *
 * La normalización vive en BranchIdentityNormalizationRule (regla pura).
 * Esta clase es el respaldo de aplicación antes de los índices únicos de BD.
 */
final class BranchUniquenessGuard
{
    public function assertAvailable(string $field, string $normalizedValue, ?int $ignoreBranchId = null): void
    {
        $column = $field === 'codigo' ? 'codigo' : 'nombre';

        $query = Branch::query()
            ->whereRaw("lower({$column}) = ?", [mb_strtolower($normalizedValue)]);

        if ($ignoreBranchId !== null) {
            $query->whereKeyNot($ignoreBranchId);
        }

        if ($query->exists()) {
            throw new DuplicateBranchFieldException($field);
        }
    }

    /**
     * Traduce una violación de índice único de PostgreSQL/SQLite a un error
     * de dominio con el campo en conflicto.
     */
    public function translateUniqueViolation(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'sucursal_codigo_normalizado_uk')) {
            throw new DuplicateBranchFieldException('codigo');
        }

        if (str_contains($message, 'sucursal_nombre_normalizado_uk')) {
            throw new DuplicateBranchFieldException('nombre');
        }
    }
}
