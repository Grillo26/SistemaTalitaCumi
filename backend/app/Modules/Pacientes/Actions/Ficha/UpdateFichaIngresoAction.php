<?php

namespace App\Modules\Pacientes\Actions\Ficha;

use App\Models\User;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoUpdated;
use App\Modules\Pacientes\Exceptions\FichaIngresoConcurrencyException;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\FichaIngresoDateRule;
use App\Modules\Pacientes\Rules\FichaIngresoStatusRule;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Support\FichaAuditAction;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;
use App\Modules\Pacientes\Support\FichaIngresoBranchResolver;
use App\Modules\Pacientes\Support\FichaIngresoRevisionWriter;
use App\Modules\Pacientes\Support\FichaIngresoSnapshot;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Editar ficha de ingreso (04.03/RF-005).
 */
final class UpdateFichaIngresoAction
{
    public function __construct(
        private readonly FichaIngresoAccessScope $scope,
        private readonly FichaIngresoStatusRule $status,
        private readonly FichaIngresoDateRule $dateRule,
        private readonly FichaIngresoRevisionWriter $revisions,
        private readonly FichaIngresoBranchResolver $branches,
        private readonly PacienteAgeCalculator $age,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        Paciente $paciente,
        FichaIngreso $ficha,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): FichaIngreso {
        $this->scope->assertCanAccess($actor, $ficha);

        return DB::transaction(function () use ($paciente, $ficha, $attributes, $expectedVersion, $actor, $ip): FichaIngreso {
            $current = FichaIngreso::query()
                ->whereKey($ficha->id)
                ->where('paciente_id', $paciente->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new FichaIngresoNotFoundException((int) $ficha->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new FichaIngresoConcurrencyException;
            }

            $this->status->assertCanEdit($current);
            $before = FichaIngresoSnapshot::from($current);
            $changes = [];

            if (array_key_exists('motivo_consulta_inicial', $attributes)) {
                $changes['motivo_consulta_inicial'] = trim((string) $attributes['motivo_consulta_inicial']);
            }

            if (array_key_exists('fecha_ingreso', $attributes)) {
                $today = $this->age->today();
                $fecha = CarbonImmutable::parse((string) $attributes['fecha_ingreso'], $today->getTimezone())->startOfDay();
                $this->dateRule->assertNotFuture($fecha, $today);
                $changes['fecha_ingreso'] = $fecha->toDateString();
            }

            foreach ([
                'composicion_familiar',
                'antecedentes_familiares',
                'observaciones',
                'derivado_por',
                'derivado_por_institucion',
                'derivado_por_contacto',
            ] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $changes[$field] = TutorIdentityRule::normalizeOptional($attributes[$field]);
                }
            }

            $realChanges = [];

            foreach ($changes as $field => $value) {
                $currentValue = $field === 'fecha_ingreso'
                    ? $current->fecha_ingreso->toDateString()
                    : $current->{$field};

                if ($currentValue !== $value) {
                    $realChanges[$field] = $value;
                }
            }

            if ($realChanges === []) {
                return $current->refresh()->load(['paciente', 'sucursalIngreso', 'tutorPrincipal']);
            }

            $current->fill($realChanges);
            $current->version = (int) $current->version + 1;
            $current->save();

            $revision = $this->revisions->record(
                $current,
                FichaAuditAction::UPDATE,
                $actor->id,
                array_keys($realChanges),
            );

            $current->refresh()->load(['paciente', 'sucursalIngreso', 'tutorPrincipal']);

            event(new FichaIngresoUpdated(
                fichaId: (int) $current->id,
                pacienteId: (int) $current->paciente_id,
                before: $before,
                after: FichaIngresoSnapshot::from($current, (int) $revision->id),
                changedFields: array_keys($realChanges),
                revisionId: (int) $revision->id,
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
