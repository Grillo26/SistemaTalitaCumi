<?php

namespace App\Modules\CatalogoServicios\Actions\CategoriasTarifa;

use App\Modules\CatalogoServicios\Events\CategoriaTarifaCreated;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Rules\NombreNormalizadoRule;
use App\Modules\CatalogoServicios\Support\CatalogoTarifasUniquenessGuard;
use App\Modules\CatalogoServicios\Support\CategoriaTarifaSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear categoría de tarifa (RF-T01).
 */
final class CreateCategoriaTarifaAction
{
    public function __construct(private readonly CatalogoTarifasUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip): CategoriaTarifa
    {
        return DB::transaction(function () use ($attributes, $actorId, $ip): CategoriaTarifa {
            $nombre = NombreNormalizadoRule::normalize((string) $attributes['nombre']);
            $descripcion = $this->nullableString($attributes['descripcion'] ?? null);

            $this->uniqueness->assertCategoriaNombreAvailable($nombre);

            try {
                $categoria = CategoriaTarifa::query()->create([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'activo' => (bool) ($attributes['activo'] ?? true),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new CategoriaTarifaCreated(
                categoriaTarifaId: $categoria->id,
                snapshot: CategoriaTarifaSnapshot::from($categoria),
                actorId: $actorId,
                ip: $ip,
            ));

            return $categoria->refresh();
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
