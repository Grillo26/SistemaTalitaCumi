<?php

namespace App\Modules\CatalogoServicios\Actions\CategoriasTarifa;

use App\Modules\CatalogoServicios\Events\CategoriaTarifaUpdated;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNotFoundException;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Support\CatalogoTarifasUniquenessGuard;
use App\Modules\CatalogoServicios\Support\CategoriaTarifaSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar categoría de tarifa (RF-T01 / RF-T02).
 */
final class UpdateCategoriaTarifaAction
{
    public function __construct(private readonly CatalogoTarifasUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $categoriaTarifaId,
        array $attributes,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): CategoriaTarifa {
        return DB::transaction(function () use (
            $categoriaTarifaId,
            $attributes,
            $version,
            $actorId,
            $ip,
        ): CategoriaTarifa {
            $categoria = CategoriaTarifa::query()->lockForUpdate()->find($categoriaTarifaId);

            if ($categoria === null) {
                throw new CategoriaTarifaNotFoundException($categoriaTarifaId);
            }

            if ((int) $categoria->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            $before = CategoriaTarifaSnapshot::from($categoria);

            if (array_key_exists('nombre', $attributes)) {
                $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
                $this->uniqueness->assertCategoriaNombreAvailable($nombre, $categoria->id);
                $categoria->nombre = $nombre;
            }

            if (array_key_exists('descripcion', $attributes)) {
                $descripcion = trim((string) $attributes['descripcion']);
                $categoria->descripcion = $descripcion === '' ? null : $descripcion;
            }

            $categoria->version = (int) $categoria->version + 1;

            try {
                $categoria->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new CategoriaTarifaUpdated(
                categoriaTarifaId: $categoria->id,
                before: $before,
                after: CategoriaTarifaSnapshot::from($categoria),
                actorId: $actorId,
                ip: $ip,
            ));

            return $categoria->refresh();
        });
    }
}
