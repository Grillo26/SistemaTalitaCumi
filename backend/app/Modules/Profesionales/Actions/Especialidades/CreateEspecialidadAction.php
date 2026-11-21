<?php

namespace App\Modules\Profesionales\Actions\Especialidades;

use App\Modules\Profesionales\Events\Especialidades\EspecialidadCreated;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Rules\EspecialidadIdentityRule;
use App\Modules\Profesionales\Rules\EspecialidadInitialStatusRule;
use App\Modules\Profesionales\Rules\EspecialidadNameUniquenessRule;
use App\Modules\Profesionales\Support\EspecialidadSnapshot;
use App\Modules\Profesionales\Support\TextNormalizer;
use App\Modules\Profesionales\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear especialidad (03/RF-E01 / 03/RN-06).
 */
final class CreateEspecialidadAction
{
    public function __construct(
        private readonly EspecialidadNameUniquenessRule $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip = null): Especialidad
    {
        $nombre = EspecialidadIdentityRule::normalizeName((string) $attributes['nombre']);

        return DB::transaction(function () use ($attributes, $nombre, $actorId, $ip): Especialidad {
            $existingId = Especialidad::query()
                ->get(['id', 'nombre'])
                ->first(static fn (Especialidad $candidate): bool => TextNormalizer::key($candidate->nombre) === TextNormalizer::key($nombre))
                ?->id;

            $this->uniqueness->assertAvailable($existingId === null ? null : (int) $existingId);

            try {
                $especialidad = Especialidad::query()->create([
                    'nombre' => $nombre,
                    'descripcion' => $this->nullableString($attributes['descripcion'] ?? null),
                    'activo' => EspecialidadInitialStatusRule::resolve(
                        array_key_exists('activo', $attributes) ? (bool) $attributes['activo'] : null,
                    ),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                UniqueViolationTranslator::especialidad($exception);
            }

            event(new EspecialidadCreated(
                especialidadId: $especialidad->id,
                snapshot: EspecialidadSnapshot::from($especialidad),
                actorId: $actorId,
                ip: $ip,
            ));

            return $especialidad->refresh();
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
