<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Modules\Profesionales\Actions\Directorio\Concerns\SyncsProfesionalEspecialidades;
use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Events\Directorio\ProfesionalCreated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalEspecialidadesUpdated;
use App\Modules\Profesionales\Exceptions\ProfesionalEspecialidadInvalidException;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Rules\ProfesionalDocumentUniquenessRule;
use App\Modules\Profesionales\Rules\ProfesionalIdentityRule;
use App\Modules\Profesionales\Rules\ProfesionalInitialStatusRule;
use App\Modules\Profesionales\Rules\ProfesionalSpecialtyAssignmentRule;
use App\Modules\Profesionales\Support\ProfesionalesModule;
use App\Modules\Profesionales\Support\ProfesionalSnapshot;
use App\Modules\Profesionales\Support\TextNormalizer;
use App\Modules\Profesionales\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear profesional (03/RF-D01, 03/RF-D02, 03/RF-D03, 03/RF-D09 / 03/RN-01, 03/RN-02).
 */
final class CreateProfesionalAction
{
    use SyncsProfesionalEspecialidades;

    public function __construct(
        private readonly ProfesionalDocumentUniquenessRule $documentUniqueness,
        private readonly ProfesionalSpecialtyAssignmentRule $specialtyAssignment,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId, ?string $ip = null): Profesional
    {
        $nombre = ProfesionalIdentityRule::normalizeName((string) $attributes['nombre_completo']);
        $ci = ProfesionalIdentityRule::normalizeCi((string) $attributes['ci']);

        $assignments = $this->assignments($attributes);
        $this->specialtyAssignment->assertValid($assignments, ProfesionalesModule::SECUNDARIAS_HABILITADAS);

        return DB::transaction(function () use ($attributes, $nombre, $ci, $assignments, $actorId, $ip): Profesional {
            $existingId = Profesional::query()
                ->get(['id', 'ci'])
                ->first(static fn (Profesional $candidate): bool => TextNormalizer::key($candidate->ci) === TextNormalizer::key($ci))
                ?->id;

            $this->documentUniqueness->assertAvailable($ci, $existingId === null ? null : (int) $existingId);
            $this->assertSpecialtiesAvailable($assignments);

            try {
                $profesional = Profesional::query()->create([
                    'nombre_completo' => $nombre,
                    'ci' => $ci,
                    'telefono' => $this->nullableString($attributes['telefono'] ?? null),
                    'telefono_2' => $this->nullableString($attributes['telefono_2'] ?? null),
                    'email' => $this->nullableString($attributes['email'] ?? null),
                    'matricula' => $this->nullableString($attributes['matricula'] ?? null),
                    'activo' => ProfesionalInitialStatusRule::resolve(
                        array_key_exists('activo', $attributes) ? (bool) $attributes['activo'] : null,
                    ),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                UniqueViolationTranslator::profesional($exception);
            }

            $this->syncEspecialidades($profesional, $assignments);
            $profesional->load(['relacionesEspecialidad.especialidad']);

            event(new ProfesionalCreated(
                profesionalId: $profesional->id,
                snapshot: ProfesionalSnapshot::from($profesional),
                actorId: $actorId,
                ip: $ip,
            ));

            event(new ProfesionalEspecialidadesUpdated(
                profesionalId: $profesional->id,
                before: null,
                after: ProfesionalSnapshot::especialidades($profesional),
                actorId: $actorId,
                ip: $ip,
            ));

            return $profesional->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{especialidad_id: int, tipo: string}>
     */
    private function assignments(array $attributes): array
    {
        $principalId = (int) ($attributes['especialidad_principal_id'] ?? 0);
        $assignments = [['especialidad_id' => $principalId, 'tipo' => TipoEspecialidad::PRINCIPAL->value]];

        foreach ($attributes['especialidades_secundarias'] ?? [] as $specialtyId) {
            $assignments[] = [
                'especialidad_id' => (int) $specialtyId,
                'tipo' => TipoEspecialidad::SECUNDARIA->value,
            ];
        }

        return $assignments;
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
