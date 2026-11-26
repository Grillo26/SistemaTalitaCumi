<?php

namespace App\Modules\CatalogoServicios\Actions\TiposServicio;

use App\Modules\CatalogoServicios\Events\TipoServicioUpdated;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\TipoServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosUniquenessGuard;
use App\Modules\CatalogoServicios\Support\TipoServicioSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar tipo de servicio (RN-C09).
 */
final class UpdateTipoServicioAction
{
    public function __construct(private readonly CatalogoServiciosUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $tipoServicioId,
        array $attributes,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): TipoServicio {
        return DB::transaction(function () use (
            $tipoServicioId,
            $attributes,
            $version,
            $actorId,
            $ip,
        ): TipoServicio {
            $tipo = TipoServicio::query()->lockForUpdate()->find($tipoServicioId);

            if ($tipo === null) {
                throw new TipoServicioNotFoundException($tipoServicioId);
            }

            if ((int) $tipo->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            $before = TipoServicioSnapshot::from($tipo);

            if (array_key_exists('nombre', $attributes)) {
                $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
                $this->uniqueness->assertTipoServicioNameAvailable($nombre, $tipo->id);
                $tipo->nombre = $nombre;
            }

            $tipo->version = (int) $tipo->version + 1;

            try {
                $tipo->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $after = TipoServicioSnapshot::from($tipo);

            event(new TipoServicioUpdated(
                tipoServicioId: $tipo->id,
                before: $before,
                after: $after,
                actorId: $actorId,
                ip: $ip,
            ));

            return $tipo->refresh();
        });
    }
}
