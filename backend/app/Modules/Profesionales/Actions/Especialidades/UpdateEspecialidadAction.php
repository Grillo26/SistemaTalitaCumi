<?php

namespace App\Modules\Profesionales\Actions\Especialidades;

use App\Modules\Profesionales\Events\Especialidades\EspecialidadUpdated;
use App\Modules\Profesionales\Exceptions\EspecialidadConcurrencyException;
use App\Modules\Profesionales\Exceptions\EspecialidadNotFoundException;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Rules\EspecialidadIdentityRule;
use App\Modules\Profesionales\Rules\EspecialidadNameUniquenessRule;
use App\Modules\Profesionales\Support\EspecialidadSnapshot;
use App\Modules\Profesionales\Support\TextNormalizer;
use App\Modules\Profesionales\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar especialidad (03/RF-E02 / 03/RN-06).
 */
final class UpdateEspecialidadAction
{
    public function __construct(
        private readonly EspecialidadNameUniquenessRule $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $especialidadId,
        array $attributes,
        int $expectedVersion,
        ?int $actorId,
        ?string $ip = null,
    ): Especialidad {
        return DB::transaction(function () use ($especialidadId, $attributes, $expectedVersion, $actorId, $ip): Especialidad {
            $especialidad = Especialidad::query()
                ->whereKey($especialidadId)
                ->lockForUpdate()
                ->first();

            if ($especialidad === null) {
                throw new EspecialidadNotFoundException($especialidadId);
            }

            if ($especialidad->version !== $expectedVersion) {
                throw new EspecialidadConcurrencyException;
            }

            $before = EspecialidadSnapshot::from($especialidad);
            $changes = [];

            if (array_key_exists('nombre', $attributes)) {
                $nombre = EspecialidadIdentityRule::normalizeName((string) $attributes['nombre']);
                $existingId = Especialidad::query()
                    ->whereKeyNot($especialidad->id)
                    ->get(['id', 'nombre'])
                    ->first(static fn (Especialidad $candidate): bool => TextNormalizer::key($candidate->nombre) === TextNormalizer::key($nombre))
                    ?->id;

                $this->uniqueness->assertAvailable($existingId === null ? null : (int) $existingId);

                if ($nombre !== $especialidad->nombre) {
                    $changes['nombre'] = $nombre;
                }
            }

            if (array_key_exists('descripcion', $attributes)) {
                $descripcion = $this->nullableString($attributes['descripcion']);

                if ($descripcion !== $especialidad->descripcion) {
                    $changes['descripcion'] = $descripcion;
                }
            }

            if ($changes === []) {
                return $especialidad;
            }

            $especialidad->fill($changes);
            $especialidad->version = $especialidad->version + 1;

            try {
                $especialidad->save();
            } catch (QueryException $exception) {
                UniqueViolationTranslator::especialidad($exception);
            }

            $especialidad->refresh();

            event(new EspecialidadUpdated(
                especialidadId: $especialidad->id,
                before: $before,
                after: EspecialidadSnapshot::from($especialidad),
                changedFields: array_keys($changes),
                actorId: $actorId,
                ip: $ip,
            ));

            return $especialidad;
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
