<?php

namespace App\Modules\CatalogoServicios\Actions\Areas;

use App\Modules\CatalogoServicios\Events\AreaCreated;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Rules\ColorRule;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Support\AreaSnapshot;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear área (RF-C01, RF-C03, RF-C04).
 */
final class CreateAreaAction
{
    public function __construct(private readonly CatalogoServiciosUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip): Area
    {
        return DB::transaction(function () use ($attributes, $actorId, $ip): Area {
            $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
            $color = ColorRule::normalizeAndValidate((string) $attributes['color']);
            $descripcion = $this->nullableString($attributes['descripcion'] ?? null);

            $this->uniqueness->assertAreaNameAvailable($nombre);

            try {
                $area = Area::query()->create([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'color' => $color,
                    'activo' => (bool) ($attributes['activo'] ?? true),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new AreaCreated(
                areaId: $area->id,
                snapshot: AreaSnapshot::from($area),
                actorId: $actorId,
                ip: $ip,
            ));

            return $area->refresh();
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
