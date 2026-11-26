<?php

namespace App\Modules\CatalogoServicios\Actions\Areas;

use App\Modules\CatalogoServicios\Events\AreaColorChanged;
use App\Modules\CatalogoServicios\Events\AreaUpdated;
use App\Modules\CatalogoServicios\Exceptions\AreaNotFoundException;
use App\Modules\CatalogoServicios\Exceptions\CatalogoConcurrencyException;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Rules\ColorRule;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Support\AreaSnapshot;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar área y su color (RF-C01 a RF-C04, RF-C14).
 */
final class UpdateAreaAction
{
    public function __construct(private readonly CatalogoServiciosUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $areaId,
        array $attributes,
        int $version,
        ?int $actorId,
        ?string $ip,
    ): Area {
        return DB::transaction(function () use ($areaId, $attributes, $version, $actorId, $ip): Area {
            $area = Area::query()->lockForUpdate()->find($areaId);

            if ($area === null) {
                throw new AreaNotFoundException($areaId);
            }

            if ((int) $area->version !== $version) {
                throw new CatalogoConcurrencyException;
            }

            $before = AreaSnapshot::from($area);
            $beforeColor = $area->color;

            if (array_key_exists('nombre', $attributes)) {
                $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
                $this->uniqueness->assertAreaNameAvailable($nombre, $area->id);
                $area->nombre = $nombre;
            }

            if (array_key_exists('descripcion', $attributes)) {
                $descripcion = trim((string) $attributes['descripcion']);
                $area->descripcion = $descripcion === '' ? null : $descripcion;
            }

            if (array_key_exists('color', $attributes)) {
                $area->color = ColorRule::normalizeAndValidate((string) $attributes['color']);
            }

            $area->version = (int) $area->version + 1;

            try {
                $area->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $after = AreaSnapshot::from($area);

            event(new AreaUpdated(
                areaId: $area->id,
                before: $before,
                after: $after,
                actorId: $actorId,
                ip: $ip,
            ));

            if ($beforeColor !== $area->color) {
                event(new AreaColorChanged(
                    areaId: $area->id,
                    beforeColor: $beforeColor,
                    afterColor: $area->color,
                    actorId: $actorId,
                    ip: $ip,
                ));
            }

            return $area->refresh();
        });
    }
}
