<?php

namespace App\Modules\Pacientes\Actions\Ficha;

use App\Models\User;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoCreated;
use App\Modules\Pacientes\Exceptions\FichaIngresoAlreadyExistsException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\FichaIngresoDateRule;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Support\FichaAuditAction;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;
use App\Modules\Pacientes\Support\FichaIngresoBranchResolver;
use App\Modules\Pacientes\Support\FichaIngresoRevisionWriter;
use App\Modules\Pacientes\Support\FichaIngresoSnapshot;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear ficha de ingreso en borrador (04.03/RF-001, 04.03/RF-002).
 */
final class CreateFichaIngresoAction
{
    public function __construct(
        private readonly FichaIngresoAccessScope $scope,
        private readonly FichaIngresoDateRule $dateRule,
        private readonly FichaIngresoRevisionWriter $revisions,
        private readonly FichaIngresoBranchResolver $branches,
        private readonly TutorDirectoryContract $tutores,
        private readonly PacienteAgeCalculator $age,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Paciente $paciente,
        array $attributes,
        User $actor,
        ?string $ip = null,
    ): FichaIngreso {
        $sucursalId = $this->scope->assertCanCreate(
            $actor,
            $paciente,
            isset($attributes['sucursal_ingreso_id']) ? (int) $attributes['sucursal_ingreso_id'] : null,
        );

        $today = $this->age->today();
        $fecha = CarbonImmutable::parse((string) $attributes['fecha_ingreso'], $today->getTimezone())->startOfDay();
        $this->dateRule->assertNotFuture($fecha, $today);

        $tutorPrincipalId = $this->tutores->tutorPrincipal($paciente->id)?->id;

        return DB::transaction(function () use (
            $paciente,
            $attributes,
            $actor,
            $ip,
            $sucursalId,
            $fecha,
            $tutorPrincipalId,
        ): FichaIngreso {
            $exists = FichaIngreso::query()
                ->vigente()
                ->where('paciente_id', $paciente->id)
                ->exists();

            if ($exists) {
                throw new FichaIngresoAlreadyExistsException;
            }

            try {
                $ficha = FichaIngreso::query()->create([
                    'paciente_id' => $paciente->id,
                    'sucursal_ingreso_id' => $sucursalId,
                    'tutor_principal_id' => $tutorPrincipalId,
                    'motivo_consulta_inicial' => trim((string) $attributes['motivo_consulta_inicial']),
                    'fecha_ingreso' => $fecha->toDateString(),
                    'composicion_familiar' => TutorIdentityRule::normalizeOptional($attributes['composicion_familiar'] ?? null),
                    'antecedentes_familiares' => TutorIdentityRule::normalizeOptional($attributes['antecedentes_familiares'] ?? null),
                    'observaciones' => TutorIdentityRule::normalizeOptional($attributes['observaciones'] ?? null),
                    'derivado_por' => TutorIdentityRule::normalizeOptional($attributes['derivado_por'] ?? null),
                    'derivado_por_institucion' => TutorIdentityRule::normalizeOptional($attributes['derivado_por_institucion'] ?? null),
                    'derivado_por_contacto' => TutorIdentityRule::normalizeOptional($attributes['derivado_por_contacto'] ?? null),
                    'estado' => EstadoFichaIngreso::BORRADOR->value,
                    'version' => 1,
                    'usuario_registro_id' => $actor->id,
                ]);
            } catch (QueryException $exception) {
                if (str_contains($exception->getMessage(), 'ficha_ingreso_abierta_uk')) {
                    throw new FichaIngresoAlreadyExistsException;
                }

                throw $exception;
            }

            $revision = $this->revisions->record($ficha, FichaAuditAction::CREATE, $actor->id);
            $ficha->refresh()->load(['paciente', 'sucursalIngreso', 'tutorPrincipal']);

            event(new FichaIngresoCreated(
                fichaId: (int) $ficha->id,
                pacienteId: (int) $ficha->paciente_id,
                snapshot: FichaIngresoSnapshot::from($ficha, (int) $revision->id),
                revisionId: (int) $revision->id,
                branchIds: $this->branches->for($ficha),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $ficha;
        });
    }
}
