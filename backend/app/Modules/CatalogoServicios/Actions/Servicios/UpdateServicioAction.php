<?php

namespace App\Modules\CatalogoServicios\Actions\Servicios;

use App\Modules\CatalogoServicios\Events\ServicioUpdated;
use App\Modules\CatalogoServicios\Exceptions\AreaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\TipoServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\TipoServicioNotFoundException;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Rules\ServicioStateRule;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosUniquenessGuard;
use App\Modules\CatalogoServicios\Support\ServicioSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar servicio (RF-C11) con control de versión y área activa.
 */
final class UpdateServicioAction
{
    public function __construct(private readonly CatalogoServiciosUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $servicioId,
        array $attributes,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): Servicio {
        return DB::transaction(function () use (
            $servicioId,
            $attributes,
            $version,
            $actorId,
            $ip,
        ): Servicio {
            $servicio = Servicio::query()->lockForUpdate()->find($servicioId);

            if ($servicio === null) {
                throw new ServicioNotFoundException($servicioId);
            }

            if ((int) $servicio->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            $before = ServicioSnapshot::from($servicio->load(['area', 'tipoServicio']));
            $targetAreaId = $servicio->area_id;

            if (array_key_exists('area_id', $attributes)) {
                $targetAreaId = (int) $attributes['area_id'];

                if ($targetAreaId !== $servicio->area_id) {
                    $area = Area::query()->lockForUpdate()->find($targetAreaId);

                    if ($area === null) {
                        throw new AreaNotFoundException($targetAreaId);
                    }

                    ServicioStateRule::assertAreaActive((bool) $area->activo, $area->nombre);
                    $servicio->area_id = $targetAreaId;
                }
            }

            if (array_key_exists('tipo_servicio_id', $attributes)) {
                $tipoServicioId = (int) $attributes['tipo_servicio_id'];

                if ($tipoServicioId !== $servicio->tipo_servicio_id) {
                    $tipoServicio = TipoServicio::query()->find($tipoServicioId);

                    if ($tipoServicio === null) {
                        throw new TipoServicioNotFoundException($tipoServicioId);
                    }

                    if (! $tipoServicio->activo) {
                        throw new TipoServicioInactivoException($tipoServicioId);
                    }

                    $servicio->tipo_servicio_id = $tipoServicioId;
                }
            }

            if (array_key_exists('nombre', $attributes)) {
                $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
                $this->uniqueness->assertServicioNameAvailable(
                    $targetAreaId,
                    $nombre,
                    $servicio->id,
                );
                $servicio->nombre = $nombre;
            } elseif ($targetAreaId !== $servicio->getOriginal('area_id')) {
                $this->uniqueness->assertServicioNameAvailable(
                    $targetAreaId,
                    $servicio->nombre,
                    $servicio->id,
                );
            }

            if (array_key_exists('descripcion', $attributes)) {
                $descripcion = trim((string) $attributes['descripcion']);
                $servicio->descripcion = $descripcion === '' ? null : $descripcion;
            }

            if (array_key_exists('duracion_minutos', $attributes)) {
                $servicio->duracion_minutos = (int) $attributes['duracion_minutos'];
            }

            $servicio->version = (int) $servicio->version + 1;

            try {
                $servicio->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $servicio->load(['area', 'tipoServicio']);
            $after = ServicioSnapshot::from($servicio);

            event(new ServicioUpdated(
                servicioId: $servicio->id,
                before: $before,
                after: $after,
                actorId: $actorId,
                ip: $ip,
            ));

            return $servicio->refresh()->load(['area', 'tipoServicio']);
        });
    }
}
