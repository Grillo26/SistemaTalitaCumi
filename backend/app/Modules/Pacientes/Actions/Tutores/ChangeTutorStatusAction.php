<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Events\Tutores\TutorStatusChanged;
use App\Modules\Pacientes\Exceptions\TutorConcurrencyException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorPrincipalActivePatientException;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorStatusChangeRule;
use App\Modules\Pacientes\Support\TutorAccessScope;
use App\Modules\Pacientes\Support\TutorBranchResolver;
use App\Modules\Pacientes\Support\TutorSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Activar/desactivar tutor (04.02/RF-010).
 */
final class ChangeTutorStatusAction
{
    public function __construct(
        private readonly TutorAccessScope $scope,
        private readonly TutorStatusChangeRule $rule,
        private readonly TutorBranchResolver $branches,
    ) {}

    public function __invoke(
        Tutor $tutor,
        bool $activo,
        ?string $motivo,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): Tutor {
        $this->scope->assertCanAccess($actor, $tutor);

        return DB::transaction(function () use ($tutor, $activo, $motivo, $expectedVersion, $actor, $ip): Tutor {
            $current = Tutor::query()->whereKey($tutor->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new TutorNotFoundException((int) $tutor->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new TutorConcurrencyException;
            }

            $this->rule->assertDifferent((bool) $current->activo, $activo);
            $reason = $this->rule->reason($activo, $motivo);

            if (! $activo) {
                $isActivePrincipal = PacienteTutor::query()
                    ->where('tutor_id', $current->id)
                    ->where('es_principal', true)
                    ->whereHas(
                        'paciente',
                        static fn ($pacientes) => $pacientes->where('estado', EstadoPaciente::ACTIVO->value),
                    )
                    ->exists();

                if ($isActivePrincipal) {
                    throw new TutorPrincipalActivePatientException;
                }
            }

            $before = TutorSnapshot::from($current);

            $current->forceFill([
                'activo' => $activo,
                'motivo_estado' => $activo ? null : $reason,
                'estado_cambiado_en' => now(),
                'version' => (int) $current->version + 1,
            ])->save();

            $current->refresh()->load(['parentescoCatalogo', 'sucursalRegistro']);

            event(new TutorStatusChanged(
                tutorId: (int) $current->id,
                before: $before,
                after: TutorSnapshot::from($current),
                activo: $activo,
                motivo: $reason,
                critico: ! $activo,
                branchIds: $this->branches->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
