<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Actions\Directorio\Concerns\SyncsProfesionalEspecialidades;
use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Events\Directorio\ProfesionalEspecialidadesUpdated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalUpdated;
use App\Modules\Profesionales\Exceptions\ProfesionalConcurrencyException;
use App\Modules\Profesionales\Exceptions\ProfesionalEspecialidadInvalidException;
use App\Modules\Profesionales\Exceptions\ProfesionalNotFoundException;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Rules\ProfesionalDocumentUniquenessRule;
use App\Modules\Profesionales\Rules\ProfesionalIdentityRule;
use App\Modules\Profesionales\Rules\ProfesionalSpecialtyAssignmentRule;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;
use App\Modules\Profesionales\Support\ProfesionalesModule;
use App\Modules\Profesionales\Support\ProfesionalSnapshot;
use App\Modules\Profesionales\Support\TextNormalizer;
use App\Modules\Profesionales\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar profesional (03/RF-D06, 03/RF-D09 / 03/RN-01, 03/RN-02).
 *
 * Los permisos por campo se validan en UpdateProfesionalRequest. Esta Action
 * valida alcance, concurrencia y reglas de negocio.
 */
final class UpdateProfesionalAction
{
    use SyncsProfesionalEspecialidades;

    public function __construct(
        private readonly ProfesionalAccessScope $scope,
        private readonly ProfesionalDocumentUniquenessRule $documentUniqueness,
        private readonly ProfesionalSpecialtyAssignmentRule $specialtyAssignment,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Profesional $profesional,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): Profesional {
        $this->scope->assertCanAccess($actor, $profesional);

        return DB::transaction(function () use ($profesional, $attributes, $expectedVersion, $actor, $ip): Profesional {
            $current = Profesional::query()
                ->whereKey($profesional->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new ProfesionalNotFoundException($profesional->id);
            }

            if ($current->version !== $expectedVersion) {
                throw new ProfesionalConcurrencyException;
            }

            $before = ProfesionalSnapshot::from($current);
            $changes = [];

            if (array_key_exists('nombre_completo', $attributes)) {
                $nombre = ProfesionalIdentityRule::normalizeName((string) $attributes['nombre_completo']);

                if ($nombre !== $current->nombre_completo) {
                    $changes['nombre_completo'] = $nombre;
                }
            }

            if (array_key_exists('ci', $attributes)) {
                $ci = ProfesionalIdentityRule::normalizeCi((string) $attributes['ci']);
                $existingId = Profesional::query()
                    ->whereKeyNot($current->id)
                    ->get(['id', 'ci'])
                    ->first(static fn (Profesional $candidate): bool => TextNormalizer::key($candidate->ci) === TextNormalizer::key($ci))
                    ?->id;

                $this->documentUniqueness->assertAvailable($ci, $existingId === null ? null : (int) $existingId);

                if ($ci !== $current->ci) {
                    $changes['ci'] = $ci;
                }
            }

            foreach (['telefono', 'telefono_2', 'email', 'matricula'] as $field) {
                if (! array_key_exists($field, $attributes)) {
                    continue;
                }

                $value = $this->nullableString($attributes[$field]);

                if ($value !== $current->{$field}) {
                    $changes[$field] = $value;
                }
            }

            $hasSpecialtyInput = array_key_exists('especialidad_principal_id', $attributes)
                || array_key_exists('especialidades_secundarias', $attributes);

            $assignments = null;

            if ($hasSpecialtyInput) {
                $assignments = $this->assignments($current, $attributes);
                $this->specialtyAssignment->assertValid($assignments, ProfesionalesModule::SECUNDARIAS_HABILITADAS);
                $this->assertSpecialtiesAvailable($assignments);
            }

            $beforeSpecialties = ProfesionalSnapshot::especialidades($current);

            if ($changes !== []) {
                $current->fill($changes);
            }

            if ($assignments !== null) {
                $this->syncEspecialidades($current, $assignments);
            }

            $afterSpecialties = ProfesionalSnapshot::especialidades($current);
            $specialtiesChanged = $beforeSpecialties !== $afterSpecialties;

            if ($changes === [] && ! $specialtiesChanged) {
                return $current;
            }

            $current->version = $current->version + 1;

            try {
                $current->save();
            } catch (QueryException $exception) {
                UniqueViolationTranslator::profesional($exception);
            }

            $current->refresh();
            $current->load(['relacionesEspecialidad.especialidad', 'relacionesSucursal.sucursal']);

            if ($changes !== []) {
                event(new ProfesionalUpdated(
                    profesionalId: $current->id,
                    before: $before,
                    after: ProfesionalSnapshot::from($current),
                    changedFields: array_keys($changes),
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            if ($specialtiesChanged) {
                event(new ProfesionalEspecialidadesUpdated(
                    profesionalId: $current->id,
                    before: $beforeSpecialties,
                    after: $afterSpecialties,
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $current;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{especialidad_id: int, tipo: string}>
     */
    private function assignments(Profesional $profesional, array $attributes): array
    {
        $principalId = array_key_exists('especialidad_principal_id', $attributes)
            ? (int) $attributes['especialidad_principal_id']
            : $this->currentPrincipalId($profesional);

        $secondaryIds = array_key_exists('especialidades_secundarias', $attributes)
            ? array_map('intval', (array) $attributes['especialidades_secundarias'])
            : $this->currentSecondaryIds($profesional);

        $assignments = [['especialidad_id' => $principalId, 'tipo' => TipoEspecialidad::PRINCIPAL->value]];

        foreach ($secondaryIds as $specialtyId) {
            $assignments[] = [
                'especialidad_id' => $specialtyId,
                'tipo' => TipoEspecialidad::SECUNDARIA->value,
            ];
        }

        return $assignments;
    }

    private function currentPrincipalId(Profesional $profesional): int
    {
        $principal = $profesional->relacionesEspecialidad()
            ->where('activo', true)
            ->where('tipo', TipoEspecialidad::PRINCIPAL->value)
            ->value('especialidad_id');

        return (int) ($principal ?? 0);
    }

    /**
     * @return list<int>
     */
    private function currentSecondaryIds(Profesional $profesional): array
    {
        return $profesional->relacionesEspecialidad()
            ->where('activo', true)
            ->where('tipo', TipoEspecialidad::SECUNDARIA->value)
            ->orderBy('especialidad_id')
            ->pluck('especialidad_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<array{especialidad_id: int, tipo: string}>  $assignments
     */
    private function assertSpecialtiesAvailable(array $assignments): void
    {
        $ids = array_map(static fn (array $assignment): int => $assignment['especialidad_id'], $assignments);
        $specialties = Especialidad::query()->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($assignments as $assignment) {
            $specialty = $specialties->get($assignment['especialidad_id']);

            if ($specialty === null || ! $specialty->activo) {
                throw new ProfesionalEspecialidadInvalidException(
                    'Una de las especialidades seleccionadas no existe o está inactiva.',
                    ['especialidad_principal_id' => ['Verificá que las especialidades estén activas.']],
                );
            }
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
