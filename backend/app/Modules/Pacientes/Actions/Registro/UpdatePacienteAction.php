<?php

namespace App\Modules\Pacientes\Actions\Registro;

use App\Models\User;
use App\Modules\Pacientes\Contracts\DiagnosticTypeDirectoryContract;
use App\Modules\Pacientes\Events\Registro\PacienteUpdated;
use App\Modules\Pacientes\Exceptions\DuplicatePacienteDocumentException;
use App\Modules\Pacientes\Exceptions\PacienteConcurrencyException;
use App\Modules\Pacientes\Exceptions\PacienteDiagnosticCatalogUnavailableException;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\PacienteBirthDateRule;
use App\Modules\Pacientes\Rules\PacienteDocumentUniquenessRule;
use App\Modules\Pacientes\Rules\PacienteIdentityRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use App\Modules\Pacientes\Support\PacienteDuplicatePresenter;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use App\Modules\Pacientes\Support\PacientesModule;
use App\Modules\Pacientes\Support\PacienteSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar paciente (04.01/RF-007, 04.01/RF-012).
 *
 * No cambia estado ni sucursal de registro. Los permisos de campos sensibles
 * se validan en UpdatePacienteRequest; la Action revalida concurrencia y
 * unicidad.
 */
final class UpdatePacienteAction
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly PacienteDocumentUniquenessRule $documentUniqueness,
        private readonly PacienteBirthDateRule $birthDateRule,
        private readonly PacienteAgeCalculator $age,
        private readonly PacienteDuplicatePresenter $duplicatePresenter,
        private readonly DiagnosticTypeDirectoryContract $diagnostics,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Paciente $paciente,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): Paciente {
        $this->scope->assertCanAccess($actor, $paciente);

        return DB::transaction(function () use ($paciente, $attributes, $expectedVersion, $actor, $ip): Paciente {
            $current = Paciente::query()->whereKey($paciente->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new PacienteNotFoundException((int) $paciente->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new PacienteConcurrencyException;
            }

            $before = PacienteSnapshot::from($current);
            $sensitiveFields = PacienteSnapshot::sensitiveFieldsPresent($attributes);
            $changes = [];

            if (array_key_exists('nombre', $attributes)) {
                $changes['nombre'] = PacienteIdentityRule::normalizeName((string) $attributes['nombre']);
            }

            if (array_key_exists('apellido_paterno', $attributes)) {
                $changes['apellido_paterno'] = PacienteIdentityRule::normalizeName((string) $attributes['apellido_paterno']);
            }

            if (array_key_exists('apellido_materno', $attributes)) {
                $changes['apellido_materno'] = PacienteIdentityRule::normalizeOptional($attributes['apellido_materno']);
            }

            if (array_key_exists('fecha_nacimiento', $attributes)) {
                $fecha = (string) $attributes['fecha_nacimiento'];
                $birthDate = CarbonImmutable::parse($fecha, $this->age->today()->getTimezone())->startOfDay();
                $this->birthDateRule->assertNotFuture($birthDate, $this->age->today());
                $changes['fecha_nacimiento'] = $birthDate->toDateString();
            }

            if (array_key_exists('sexo', $attributes)) {
                $changes['sexo'] = (string) $attributes['sexo'];
            }

            if (array_key_exists('ci', $attributes)) {
                $documento = PacienteIdentityRule::normalizeDocument($attributes['ci']);
                $documentKey = PacienteNormalizer::documentKey($documento);

                $existing = $documentKey === null
                    ? null
                    : Paciente::query()
                        ->whereKeyNot($current->id)
                        ->where('documento_normalizado', $documentKey)
                        ->first();

                if (! $this->documentUniqueness->isAvailable(
                    $existing === null ? null : (int) $existing->id,
                )) {
                    throw new DuplicatePacienteDocumentException(
                        $this->duplicatePresenter->hard($actor, $existing),
                    );
                }

                $changes['ci'] = $documento;
            }

            if (array_key_exists('tipo_diagnostico_id', $attributes)) {
                $tipoDiagnosticoId = $attributes['tipo_diagnostico_id'] === null
                    ? null
                    : (int) $attributes['tipo_diagnostico_id'];

                if ($tipoDiagnosticoId !== null) {
                    if (! PacientesModule::DIAGNOSTICOS_DISPONIBLES) {
                        throw new PacienteDiagnosticCatalogUnavailableException;
                    }

                    $this->diagnostics->asegurarActivo($tipoDiagnosticoId);
                }

                $changes['tipo_diagnostico_id'] = $tipoDiagnosticoId;
            }

            foreach (['alergias', 'condiciones_medicas', 'observaciones'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $changes[$field] = PacienteIdentityRule::normalizeOptional($attributes[$field]);
                }
            }

            $realChanges = [];

            foreach ($changes as $field => $value) {
                $currentValue = match ($field) {
                    'fecha_nacimiento' => $current->fecha_nacimiento->toDateString(),
                    'sexo' => $current->sexo->value,
                    'tipo_diagnostico_id' => $current->tipo_diagnostico_id === null ? null : (int) $current->tipo_diagnostico_id,
                    default => $current->{$field},
                };

                if ($currentValue !== $value) {
                    $realChanges[$field] = $value;
                }
            }

            if ($realChanges === []) {
                return $current->refresh()->load(['sucursalRegistro', 'relacionesTutor.tutor']);
            }

            $current->fill($realChanges);
            $current->version = (int) $current->version + 1;

            try {
                $current->save();
            } catch (QueryException $exception) {
                $documentKey = PacienteNormalizer::documentKey($current->ci);
                $existing = $documentKey === null
                    ? null
                    : Paciente::query()
                        ->whereKeyNot($current->id)
                        ->where('documento_normalizado', $documentKey)
                        ->first();

                if ($existing !== null) {
                    throw new DuplicatePacienteDocumentException(
                        $this->duplicatePresenter->hard($actor, $existing),
                    );
                }

                throw $exception;
            }

            $current->refresh()->load(['sucursalRegistro', 'relacionesTutor.tutor']);

            event(new PacienteUpdated(
                pacienteId: (int) $current->id,
                before: $before,
                after: PacienteSnapshot::from($current),
                changedFields: array_keys($realChanges),
                sensitiveFields: $sensitiveFields,
                branchIds: [(int) $current->sucursal_registro_id],
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
