<?php

namespace App\Modules\CatalogoServicios\Actions\TiposServicio;

use App\Modules\CatalogoServicios\Events\TipoServicioCreated;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosUniquenessGuard;
use App\Modules\CatalogoServicios\Support\TipoServicioSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear tipo de servicio configurable (RN-C09).
 */
final class CreateTipoServicioAction
{
    public function __construct(private readonly CatalogoServiciosUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip): TipoServicio
    {
        return DB::transaction(function () use ($attributes, $actorId, $ip): TipoServicio {
            $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
            $this->uniqueness->assertTipoServicioNameAvailable($nombre);

            try {
                $tipo = TipoServicio::query()->create([
                    'nombre' => $nombre,
                    'activo' => (bool) ($attributes['activo'] ?? true),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new TipoServicioCreated(
                tipoServicioId: $tipo->id,
                snapshot: TipoServicioSnapshot::from($tipo),
                actorId: $actorId,
                ip: $ip,
            ));

            return $tipo->refresh();
        });
    }
}
