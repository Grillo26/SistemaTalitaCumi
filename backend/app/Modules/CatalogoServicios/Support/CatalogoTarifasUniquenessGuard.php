<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNombreDuplicadoException;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Rules\CategoriaTarifaNombreUnicoRule;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use Illuminate\Database\QueryException;

/**
 * Unicidad de categorías contra la BD.
 */
final class CatalogoTarifasUniquenessGuard
{
    public function assertCategoriaNombreAvailable(string $nombre, ?int $ignoreId = null): void
    {
        $candidate = NombreNormalizadoRule::fold($nombre);

        $existing = CategoriaTarifa::query()
            ->when($ignoreId !== null, static fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('nombre')
            ->map(static fn (string $name): string => NombreNormalizadoRule::fold($name))
            ->all();

        CategoriaTarifaNombreUnicoRule::assertAvailable($candidate, $existing);
    }

    public function translateUniqueViolation(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'categoria_tarifa_nombre_normalizado_uk')
            || str_contains($message, 'categoria_tarifa_nombre_unique')) {
            throw new CategoriaTarifaNombreDuplicadoException;
        }
    }
}
