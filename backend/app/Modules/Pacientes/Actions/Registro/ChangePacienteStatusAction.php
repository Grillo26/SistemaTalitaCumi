<?php

namespace App\Modules\Pacientes\Actions\Registro;

use App\Models\User;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Events\Registro\PacienteStatusChanged;
use App\Modules\Pacientes\Exceptions\PacienteConcurrencyException;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteStatusUnchangedException;
use App\Modules\Pacientes\Models\EstadoPacienteHistorial;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\PacienteStatusChangeRule;
use App\Modules\Pacientes\Rules\PacienteStatusTransitionRule;
use App\Modules\Pacientes\Rules\PacienteTutorRequirementRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use App\Modules\Pacientes\Support\PacienteSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Cambiar estado del paciente (04.01/RF-008, 04.01/RN-004/RN-005).
 */
final class ChangePacienteStatusAction
{
    public function __construct(
        private readonly PacienteAccessScope $scope,
        private readonly PacienteStatusTransitionRule $transitionRule,
        private readonly PacienteStatusChangeRule $changeRule,
        private readonly PacienteTutorRequirementRule $tutorRequirement,
        private readonly TutorDirectoryContract $tutors,
        private readonly PacienteBranchResolver $branchResolver,
    ) {}

    public function __invoke(
        Paciente $paciente,
        EstadoPaciente $target,
        ?string $motivo,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): Paciente {
        $this->scope->assertCanAccess($actor, $paciente);

        return DB::transaction(function () use ($paciente, $target, $motivo, $expectedVersion, $actor, $ip): Paciente {
            $current = Paciente::query()->whereKey($paciente->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new PacienteNotFoundException((int) $paciente->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new PacienteConcurrencyException;
            }

            $from = $current->estado;

            if ($from === $target) {
                throw new PacienteStatusUnchangedException($target);
            }

            $this->transitionRule->assertAllowed($from, $target);
            $reason = $this->changeRule->reason($from, $target, $motivo);

            if ($target === EstadoPaciente::ACTIVO) {
                $this->tutorRequirement->assertCanActivate(
                    $this->tutors->tieneTutorPrincipal((int) $current->id),
                );
            }

            $before = PacienteSnapshot::from($current);

            $current->forceFill([
                'estado' => $target->value,
                'version' => (int) $current->version + 1,
            ])->save();

            EstadoPacienteHistorial::query()->create([
                'paciente_id' => $current->id,
                'estado_anterior' => $from->value,
                'estado_nuevo' => $target->value,
                'motivo' => $reason,
                'usuario_id' => $actor->id,
                'created_at' => now(),
            ]);

            $current->refresh()->load(['sucursalRegistro', 'relacionesTutor.tutor']);

            event(new PacienteStatusChanged(
                pacienteId: (int) $current->id,
                before: $before,
                after: PacienteSnapshot::from($current),
                from: $from,
                to: $target,
                motivo: $reason,
                critico: $target->isCriticalTransitionFrom($from),
                branchIds: $this->branchResolver->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
