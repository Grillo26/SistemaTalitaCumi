<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\TutorCreated;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Rules\TutorUniquenessRule;
use App\Modules\Pacientes\Support\TutorSnapshot;
use App\Modules\Pacientes\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear tutor (04.02/RF-001, 04.02/RF-003).
 *
 * Se usa tanto desde el alta de paciente como desde el buscador de tutores.
 */
final class CreateTutorAction
{
    public function __construct(private readonly TutorUniquenessRule $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        array $attributes,
        ?int $actorId,
        ?string $ip = null,
        ?int $branchId = null,
    ): Tutor {
        $nombre = TutorIdentityRule::normalizeName((string) $attributes['nombre_completo']);
        $ci = TutorIdentityRule::normalizeDocument($attributes['ci'] ?? null);
        $telefono = TutorIdentityRule::normalizePhone((string) $attributes['telefono']);
        $documentKey = \App\Modules\Pacientes\Support\PacienteNormalizer::documentKey($ci);
        $nameKey = \App\Modules\Pacientes\Support\PacienteNormalizer::key($nombre);
        $phoneKey = \App\Modules\Pacientes\Support\PacienteNormalizer::phoneKey($telefono);

        $existingDocId = $documentKey === null
            ? null
            : Tutor::query()->where('documento_normalizado', $documentKey)->value('id');

        $existingNamePhoneId = Tutor::query()
            ->where('nombre_completo_normalizado', $nameKey)
            ->where('telefono_normalizado', $phoneKey)
            ->value('id');

        $this->uniqueness->assertAvailable(
            $existingDocId === null ? null : (int) $existingDocId,
            $existingNamePhoneId === null ? null : (int) $existingNamePhoneId,
        );

        return DB::transaction(function () use ($attributes, $nombre, $ci, $telefono, $actorId, $ip, $branchId): Tutor {
            try {
                $tutor = Tutor::query()->create([
                    'nombre_completo' => $nombre,
                    'ci' => $ci,
                    'telefono' => $telefono,
                    'telefono_alternativo' => TutorIdentityRule::normalizeOptional($attributes['telefono_alternativo'] ?? null),
                    'email' => TutorIdentityRule::normalizeOptional($attributes['email'] ?? null),
                    'direccion' => TutorIdentityRule::normalizeOptional($attributes['direccion'] ?? null),
                    'parentesco' => TutorIdentityRule::normalizeOptional($attributes['parentesco'] ?? null),
                    'activo' => true,
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                UniqueViolationTranslator::tutor($exception);
            }

            $tutor->refresh();

            event(new TutorCreated(
                tutorId: (int) $tutor->id,
                snapshot: TutorSnapshot::from($tutor),
                branchIds: [$branchId],
                actorId: $actorId,
                ip: $ip,
            ));

            return $tutor;
        });
    }
}
