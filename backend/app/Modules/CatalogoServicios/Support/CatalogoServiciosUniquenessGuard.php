<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\CatalogoServicios\Exceptions\AreaNombreDuplicadoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNombreDuplicadoException;
use App\Modules\CatalogoServicios\Exceptions\TipoServicioNombreDuplicadoException;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\CatalogoServicios\Rules\AreaNombreUnicoRule;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Rules\ServicioNombreUnicoRule;
use App\Modules\CatalogoServicios\Rules\TipoServicioNombreUnicoRule;
use Illuminate\Database\QueryException;

/**
 * Comprobaciones de unicidad contra la BD.
 *
 * La comparación se hace en PHP con `NombreNormalizadoRule::fold` para que
 * SQLite y PostgreSQL se comporten igual con acentos. Los índices únicos
 * funcionales son el respaldo definitivo.
 */
final class CatalogoServiciosUniquenessGuard
{
    public function assertAreaNameAvailable(string $nombre, ?int $ignoreId = null): void
    {
        $candidate = NombreNormalizadoRule::fold($nombre);

        $existing = Area::query()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('nombre')
            ->map(static fn (string $name): string => NombreNormalizadoRule::fold($name))
            ->all();

        AreaNombreUnicoRule::assertAvailable($candidate, $existing);
    }

    public function assertServicioNameAvailable(
        int $areaId,
        string $nombre,
        ?int $ignoreId = null,
    ): void {
        $candidate = NombreNormalizadoRule::fold($nombre);

        $existing = Servicio::query()
            ->where('area_id', $areaId)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('nombre')
            ->map(static fn (string $name): string => NombreNormalizadoRule::fold($name))
            ->all();

        ServicioNombreUnicoRule::assertAvailable($candidate, $existing);
    }

    public function assertTipoServicioNameAvailable(string $nombre, ?int $ignoreId = null): void
    {
        $candidate = NombreNormalizadoRule::fold($nombre);

        $existing = TipoServicio::query()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('nombre')
            ->map(static fn (string $name): string => NombreNormalizadoRule::fold($name))
            ->all();

        TipoServicioNombreUnicoRule::assertAvailable($candidate, $existing);
    }

    public function translateUniqueViolation(QueryException $exception): void
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'area_nombre_normalizado_uk')
            || str_contains($message, 'area_nombre_unique')) {
            throw new AreaNombreDuplicadoException;
        }

        if (str_contains($message, 'servicio_area_nombre_normalizado_uk')) {
            throw new ServicioNombreDuplicadoException;
        }

        if (str_contains($message, 'tipo_servicio_nombre_normalizado_uk')) {
            throw new TipoServicioNombreDuplicadoException;
        }
    }
}
