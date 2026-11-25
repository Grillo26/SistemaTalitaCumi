<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\TutorUpdated;
use App\Modules\Pacientes\Exceptions\DuplicateTutorException;
use App\Modules\Pacientes\Exceptions\TutorConcurrencyException;
use App\Modules\Pacientes\Exceptions\TutorIdentityOutOfScopeException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorPhoneInvalidException;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorIdentityEditScopeRule;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Rules\TutorPhoneRule;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use App\Modules\Pacientes\Support\ParentescoResolver;
use App\Modules\Pacientes\Support\TutorAccessScope;
use App\Modules\Pacientes\Support\TutorBranchResolver;
use App\Modules\Pacientes\Support\TutorDuplicateDetector;
use App\Modules\Pacientes\Support\TutorDuplicatePresenter;
use App\Modules\Pacientes\Support\TutorSnapshot;
use App\Modules\Pacientes\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar tutor (04.02/RF-009).
 *
 * Identidad y contacto se autorizan por campo en UpdateTutorRequest. Acá se
 * revalida el alcance de identidad cuando el tutor está compartido entre
 * sucursales.
 */
final class UpdateTutorAction
{
    public function __construct(
        private readonly TutorAccessScope $scope,
        private readonly TutorBranchResolver $branches,
        private readonly TutorIdentityEditScopeRule $identityScope,
        private readonly ParentescoResolver $parentescos,
        private readonly TutorDuplicateDetector $duplicates,
        private readonly TutorDuplicatePresenter $duplicatePresenter,
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
    ): Tutor {
        $this->scope->assertCanAccess($actor, $tutor);

        $identityFields = ['nombre_completo', 'ci', 'parentesco_id', 'parentesco'];
        $hasIdentityChanges = array_intersect($identityFields, array_keys($attributes)) !== [];

        if ($hasIdentityChanges && ! $this->identityScope->canEditIdentity(
            $this->scope->accessibleBranchIds($actor),
            $this->branches->for($tutor),
        )) {
            throw new TutorIdentityOutOfScopeException;
        }

        return DB::transaction(function () use ($tutor, $attributes, $expectedVersion, $actor, $ip): Tutor {
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
                $phone = TutorPhoneRule::normalize((string) $attributes['telefono']);

                if (! TutorPhoneRule::isValidPrincipal($phone)) {
                    throw new TutorPhoneInvalidException('telefono');
                }

                $changes['telefono'] = $phone;
            }

            if (array_key_exists('telefono_alternativo', $attributes)) {
                $phone = TutorPhoneRule::normalize($attributes['telefono_alternativo']);

                if ($phone !== null && ! TutorPhoneRule::isValidAlternate($phone)) {
                    throw new TutorPhoneInvalidException('telefono_alternativo');
                }

                $changes['telefono_alternativo'] = $phone;
            }

            foreach (['email', 'direccion'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $changes[$field] = TutorIdentityRule::normalizeOptional($attributes[$field]);
                }
            }

            if (array_key_exists('parentesco_id', $attributes) || array_key_exists('parentesco', $attributes)) {
                $parentesco = $this->parentescos->resolve(
                    isset($attributes['parentesco_id']) ? (int) $attributes['parentesco_id'] : null,
                    $attributes['parentesco'] ?? null,
                );

                $changes['parentesco_id'] = (int) $parentesco->id;
                $changes['parentesco'] = $parentesco->nombre;
            }

            $documentKey = PacienteNormalizer::documentKey(
                $changes['ci'] ?? $current->ci,
            );
            $nameKey = PacienteNormalizer::key(
                $changes['nombre_completo'] ?? $current->nombre_completo,
            );
            $phoneKey = PacienteNormalizer::phoneKey(
                $changes['telefono'] ?? $current->telefono,
            );

            $duplicates = $this->duplicates->detect(
                $nameKey,
                $documentKey,
                $phoneKey,
                (int) $current->id,
            );

            if ($duplicates->hasConflict()) {
                throw new DuplicateTutorException(
                    $duplicates->errors(),
                    $this->duplicatePresenter->for($actor, $duplicates),
                );
            }

            $realChanges = [];

            foreach ($changes as $field => $value) {
                $currentValue = match ($field) {
                    'parentesco_id' => $current->parentesco_id === null ? null : (int) $current->parentesco_id,
                    default => $current->{$field},
                };

                if ($currentValue !== $value) {
                    $realChanges[$field] = $value;
                }
            }

            if ($realChanges === []) {
                return $current->refresh()->load(['parentescoCatalogo', 'sucursalRegistro']);
            }

            $current->fill($realChanges);
            $current->version = (int) $current->version + 1;

            try {
                $current->save();
            } catch (QueryException $exception) {
                UniqueViolationTranslator::tutor($exception);
            }

            $current->refresh()->load(['parentescoCatalogo', 'sucursalRegistro']);

            event(new TutorUpdated(
                tutorId: (int) $current->id,
                before: $before,
                after: TutorSnapshot::from($current),
                changedFields: array_keys($realChanges),
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
