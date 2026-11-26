<?php

namespace App\Modules\CatalogoServicios\Actions\Servicios;

use App\Modules\CatalogoServicios\Events\ServicioCreated;
use App\Modules\CatalogoServicios\Exceptions\AreaNotFoundException;
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
 * Crear servicio (RF-C05 a RF-C07, RN-C04, RN-C07).
 */
final class CreateServicioAction
{
    public function __construct(private readonly CatalogoServiciosUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip): Servicio
    {
        return DB::transaction(function () use ($attributes, $actorId, $ip): Servicio {
            $areaId = (int) $attributes['area_id'];
            $tipoServicioId = (int) $attributes['tipo_servicio_id'];

            $area = Area::query()->lockForUpdate()->find($areaId);

            if ($area === null) {
                throw new AreaNotFoundException($areaId);
            }

            ServicioStateRule::assertAreaActive((bool) $area->activo, $area->nombre);

            $tipoServicio = TipoServicio::query()->find($tipoServicioId);

            if ($tipoServicio === null) {
                throw new TipoServicioNotFoundException($tipoServicioId);
            }

            if (! $tipoServicio->activo) {
                throw new TipoServicioInactivoException($tipoServicioId);
            }

            $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
            $this->uniqueness->assertServicioNameAvailable($areaId, $nombre);

            try {
                $servicio = Servicio::query()->create([
                    'area_id' => $areaId,
                    'tipo_servicio_id' => $tipoServicioId,
                    'nombre' => $nombre,
                    'descripcion' => $this->nullableString($attributes['descripcion'] ?? null),
                    'duracion_minutos' => (int) $attributes['duracion_minutos'],
                    'activo' => (bool) ($attributes['activo'] ?? true),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $servicio->load(['area', 'tipoServicio']);

            event(new ServicioCreated(
                servicioId: $servicio->id,
                snapshot: ServicioSnapshot::from($servicio),
                actorId: $actorId,
                ip: $ip,
            ));

            return $servicio->refresh()->load(['area', 'tipoServicio']);
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
