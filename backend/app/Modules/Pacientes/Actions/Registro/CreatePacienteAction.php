<?php

namespace App\Modules\Pacientes\Actions\Registro;

use App\Models\User;
use App\Modules\Pacientes\Actions\Tutores\AssociateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\CreateTutorAction;
use App\Modules\Pacientes\Contracts\DiagnosticTypeDirectoryContract;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Events\Registro\PacienteCreated;
use App\Modules\Pacientes\Events\Registro\PacienteDuplicateConfirmed;
use App\Modules\Pacientes\Exceptions\DuplicatePacienteDocumentException;
use App\Modules\Pacientes\Exceptions\PacienteDuplicateConfirmationReasonRequiredException;
use App\Modules\Pacientes\Exceptions\PacienteDiagnosticCatalogUnavailableException;
use App\Modules\Pacientes\Exceptions\PacienteTutorRequiredException;
use App\Modules\Pacientes\Exceptions\PotentialDuplicatePacienteException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\EstadoPacienteHistorial;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\PacienteBirthDateRule;
use App\Modules\Pacientes\Rules\PacienteIdentityRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use App\Modules\Pacientes\Support\PacienteDuplicateDetector;
use App\Modules\Pacientes\Support\PacienteDuplicatePresenter;
use App\Modules\Pacientes\Support\PacienteNormalizer;
use App\Modules\Pacientes\Support\PacienteSnapshot;
use App\Modules\Pacientes\Support\PacientesModule;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear paciente (04.01/RF-001..RF-004, 04.01/RF-016).
 *
 * El paciente nace activo y con tutor principal en la misma transacción:
 * nunca existe un paciente activo sin tutor (04.01/RN-003).
 */
final class CreatePacienteAction
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly PacienteDuplicateDetector $detector,
        private readonly PacienteDuplicatePresenter $duplicatePresenter,
        private readonly PacienteBirthDateRule $birthDateRule,
        private readonly PacienteAgeCalculator $age,
        private readonly TutorAccessScope $tutorScope,
        private readonly CreateTutorAction $createTutor,
        private readonly AssociateTutorAction $associateTutor,
        private readonly DiagnosticTypeDirectoryContract $diagnostics,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, User $actor, ?string $ip = null): Paciente
    {
        $nombre = PacienteIdentityRule::normalizeName((string) $attributes['nombre']);
        $apellidoPaterno = PacienteIdentityRule::normalizeName((string) $attributes['apellido_paterno']);
        $apellidoMaterno = PacienteIdentityRule::normalizeOptional($attributes['apellido_materno'] ?? null);
        $documento = PacienteIdentityRule::normalizeDocument($attributes['ci'] ?? null);
        $fechaNacimiento = (string) $attributes['fecha_nacimiento'];
        $sexo = (string) $attributes['sexo'];
        $branchId = (int) $attributes['sucursal_registro_id'];
        $tipoDiagnosticoId = isset($attributes['tipo_diagnostico_id']) && $attributes['tipo_diagnostico_id'] !== null
            ? (int) $attributes['tipo_diagnostico_id']
            : null;

        $this->scope->assertBranchAllowed($actor, $branchId);
        $this->assertDiagnosticAvailable($tipoDiagnosticoId);

        $birthDate = CarbonImmutable::parse($fechaNacimiento, $this->age->today()->getTimezone())->startOfDay();
        $this->birthDateRule->assertNotFuture($birthDate, $this->age->today());

        $tutorInput = $attributes['tutor_principal'] ?? null;

        if (! is_array($tutorInput) || ! in_array($tutorInput['modo'] ?? null, ['existente', 'nuevo'], true)) {
            throw new PacienteTutorRequiredException;
        }

        $existingTutor = null;
        $tutorPrincipalId = null;

        if ($tutorInput['modo'] === 'existente') {
            $existingTutor = Tutor::query()->find((int) ($tutorInput['tutor_id'] ?? 0));

            if ($existingTutor === null) {
                throw new TutorNotFoundException((int) ($tutorInput['tutor_id'] ?? 0));
            }

            $this->tutorScope->assertCanAssociate($actor, $existingTutor);
            $tutorPrincipalId = (int) $existingTutor->id;
        }

        $duplicates = $this->detector->detect([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'fecha_nacimiento' => $fechaNacimiento,
            'documento' => $documento,
            'tutor_principal_id' => $tutorPrincipalId,
        ]);

        if ($duplicates->hardDocument !== null) {
            throw new DuplicatePacienteDocumentException(
                $this->duplicatePresenter->hard($actor, $duplicates->hardDocument),
            );
        }

        $confirmarDuplicado = (bool) ($attributes['confirmar_duplicado'] ?? false);
        $motivoConfirmacion = trim((string) ($attributes['motivo_confirmacion'] ?? ''));

        if ($duplicates->hasSoftMatches() && ! $confirmarDuplicado) {
            throw new PotentialDuplicatePacienteException(
                $this->duplicatePresenter->soft($actor, $duplicates),
            );
        }

        if ($duplicates->hasSoftMatches() && $confirmarDuplicado && $motivoConfirmacion === '') {
            throw new PacienteDuplicateConfirmationReasonRequiredException;
        }

        return DB::transaction(function () use (
            $attributes,
            $actor,
            $ip,
            $nombre,
            $apellidoPaterno,
            $apellidoMaterno,
            $documento,
            $fechaNacimiento,
            $sexo,
            $branchId,
            $tipoDiagnosticoId,
            $tutorInput,
            $existingTutor,
            $duplicates,
            $confirmarDuplicado,
            $motivoConfirmacion,
        ): Paciente {
            try {
                $paciente = Paciente::query()->create([
                    'codigo' => null,
                    'nombre' => $nombre,
                    'apellido_paterno' => $apellidoPaterno,
                    'apellido_materno' => $apellidoMaterno,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'sexo' => $sexo,
                    'ci' => $documento,
                    'tipo_diagnostico_id' => $tipoDiagnosticoId,
                    'sucursal_registro_id' => $branchId,
                    'estado' => EstadoPaciente::ACTIVO->value,
                    'alergias' => PacienteIdentityRule::normalizeOptional($attributes['alergias'] ?? null),
                    'condiciones_medicas' => PacienteIdentityRule::normalizeOptional($attributes['condiciones_medicas'] ?? null),
                    'observaciones' => PacienteIdentityRule::normalizeOptional($attributes['observaciones'] ?? null),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $existing = $documento === null
                    ? null
                    : Paciente::query()
                        ->where('documento_normalizado', PacienteNormalizer::documentKey($documento))
                        ->first();

                if ($existing !== null) {
                    throw new DuplicatePacienteDocumentException(
                        $this->duplicatePresenter->hard($actor, $existing),
                    );
                }

                throw $exception;
            }

            $paciente->forceFill([
                'codigo' => 'PAC-'.str_pad((string) $paciente->id, 6, '0', STR_PAD_LEFT),
            ])->saveQuietly();

            EstadoPacienteHistorial::query()->create([
                'paciente_id' => $paciente->id,
                'estado_anterior' => null,
                'estado_nuevo' => EstadoPaciente::ACTIVO->value,
                'motivo' => null,
                'usuario_id' => $actor->id,
                'created_at' => now(),
            ]);

            if ($existingTutor !== null) {
                $this->associatePrincipalTutor($paciente, $existingTutor, $actor, $ip);
            } else {
                $tutor = ($this->createTutor)($tutorInput, $actor->id, $ip, $branchId);
                $this->associatePrincipalTutor($paciente, $tutor, $actor, $ip);
            }

            $paciente->refresh()->load(['sucursalRegistro', 'relacionesTutor.tutor']);

            event(new PacienteCreated(
                pacienteId: (int) $paciente->id,
                snapshot: PacienteSnapshot::from($paciente),
                branchIds: [$branchId],
                actorId: $actor->id,
                ip: $ip,
            ));

            if ($confirmarDuplicado && $duplicates->hasSoftMatches()) {
                event(new PacienteDuplicateConfirmed(
                    pacienteId: (int) $paciente->id,
                    motivo: $motivoConfirmacion,
                    coincidenciasEnAlcance: $this->duplicatePresenter->inScopeIds($actor, $duplicates),
                    huboFueraDeAlcance: $this->duplicatePresenter->hasOutOfScope($actor, $duplicates),
                    branchIds: [$branchId],
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $paciente;
        });
    }

    private function associatePrincipalTutor(
        Paciente $paciente,
        Tutor $tutor,
        User $actor,
        ?string $ip,
    ): void {
        ($this->associateTutor)($paciente, $tutor, true, $actor, $ip);
    }

    private function assertDiagnosticAvailable(?int $tipoDiagnosticoId): void
    {
        if ($tipoDiagnosticoId === null) {
            return;
        }

        if (! PacientesModule::DIAGNOSTICOS_DISPONIBLES) {
            throw new PacienteDiagnosticCatalogUnavailableException;
        }

        $this->diagnostics->asegurarActivo($tipoDiagnosticoId);
    }
}
