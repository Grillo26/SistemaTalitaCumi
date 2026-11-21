<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\TutorUpdated;
use App\Modules\Pacientes\Exceptions\TutorConcurrencyException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Rules\TutorUniquenessRule;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use App\Modules\Pacientes\Support\TutorAccessScope;
use App\Modules\Pacientes\Support\TutorSnapshot;
use App\Modules\Pacientes\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar tutor (04.02/RF-009).
 */
final class UpdateTutorAction
{
    public function __construct(
        private readonly TutorAccessScope $scope,
        private readonly TutorUniquenessRule $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Tutor $tutor,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
        ?int $branchId = null,
    ): Tutor {
        $this->scope->assertCanAccess($actor, $tutor);

        return DB::transaction(function () use ($tutor, $attributes, $expectedVersion, $actor, $ip, $branchId): Tutor {
            $current = Tutor::query()->whereKey($tutor->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new TutorNotFoundException((int) $tutor->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new TutorConcurrencyException;
            }

            $before = TutorSnapshot::from($current);
            $changes = [];

            if (array_key_exists('nombre_completo', $attributes)) {
                $changes['nombre_completo'] = TutorIdentityRule::normalizeName((string) $attributes['nombre_completo']);
            }

            if (array_key_exists('ci', $attributes)) {
                $changes['ci'] = TutorIdentityRule::normalizeDocument($attributes['ci']);
            }

            if (array_key_exists('telefono', $attributes)) {
                $changes['telefono'] = TutorIdentityRule::normalizePhone((string) $attributes['telefono']);
            }

            foreach (['telefono_alternativo', 'email', 'direccion', 'parentesco'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $changes[$field] = TutorIdentityRule::normalizeOptional($attributes[$field]);
                }
            }

            $documentKey = PacienteNormalizer::documentKey($changes['ci'] ?? $current->ci);
            $nameKey = PacienteNormalizer::key($changes['nombre_completo'] ?? $current->nombre_completo);
            $phoneKey = PacienteNormalizer::phoneKey($changes['telefono'] ?? $current->telefono);

            $existingDocId = $documentKey === null
                ? null
                : Tutor::query()
                    ->whereKeyNot($current->id)
                    ->where('documento_normalizado', $documentKey)
                    ->value('id');

            $existingNamePhoneId = Tutor::query()
                ->whereKeyNot($current->id)
                ->where('nombre_completo_normalizado', $nameKey)
                ->where('telefono_normalizado', $phoneKey)
                ->value('id');

            $this->uniqueness->assertAvailable(
                $existingDocId === null ? null : (int) $existingDocId,
                $existingNamePhoneId === null ? null : (int) $existingNamePhoneId,
            );

            $realChanges = [];

            foreach ($changes as $field => $value) {
                if ($current->{$field} !== $value) {
                    $realChanges[$field] = $value;
                }
            }

            if ($realChanges === []) {
                return $current;
            }

            $current->fill($realChanges);
            $current->version = (int) $current->version + 1;

            try {
                $current->save();
            } catch (QueryException $exception) {
                UniqueViolationTranslator::tutor($exception);
            }

            $current->refresh();

            event(new TutorUpdated(
                tutorId: (int) $current->id,
                before: $before,
                after: TutorSnapshot::from($current),
                changedFields: array_keys($realChanges),
                branchIds: [$branchId],
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
