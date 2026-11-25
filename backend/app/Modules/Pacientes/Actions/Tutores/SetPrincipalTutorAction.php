<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteTutorNotFoundException;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorPrincipalChangeRule;
use App\Modules\Pacientes\Rules\TutorPrincipalRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Illuminate\Support\Facades\DB;

/**
 * Designar tutor principal (04.02/RF-005).
 */
final class SetPrincipalTutorAction
{
    public function __construct(
        private readonly PacienteAccessScope $pacienteScope,
        private readonly TutorAccessScope $tutorScope,
        private readonly TutorPrincipalRule $principalRule,
        private readonly TutorPrincipalChangeRule $principalChangeRule,
        private readonly PacienteBranchResolver $branchResolver,
    ) {}

    public function __invoke(
        Paciente $paciente,
        Tutor $tutor,
        User $actor,
        ?string $ip = null,
        ?string $motivo = null,
    ): PacienteTutor {
        $this->pacienteScope->assertCanAccess($actor, $paciente);
        $this->tutorScope->assertCanAssociate($actor, $tutor);
        $this->principalRule->assertCanBePrincipal((bool) $tutor->activo);

        return DB::transaction(function () use ($paciente, $tutor, $actor, $ip, $motivo): PacienteTutor {
            $currentTutor = Tutor::query()->whereKey($tutor->id)->lockForUpdate()->first();

            if ($currentTutor === null) {
                throw new TutorNotFoundException((int) $tutor->id);
            }

            if (! $currentTutor->activo) {
                throw new TutorInactiveException;
            }

            $current = Paciente::query()->whereKey($paciente->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new PacienteNotFoundException((int) $paciente->id);
            }

            $relation = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $currentTutor->id)
                ->first();

            if ($relation === null) {
                throw new PacienteTutorNotFoundException((int) $current->id, (int) $currentTutor->id);
            }

            $previousPrincipal = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('es_principal', true)
                ->first();

            if ($previousPrincipal !== null && (int) $previousPrincipal->tutor_id === (int) $currentTutor->id) {
                return $relation;
            }

            $reason = $this->principalChangeRule->reason($previousPrincipal !== null, $motivo);

            PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->update(['es_principal' => false]);

            // PK compuesta: actualizar por query, no con save() del modelo.
            PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $currentTutor->id)
                ->update(['es_principal' => true]);

            $relation = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $currentTutor->id)
                ->firstOrFail();

            event(new PacienteTutorPrincipalChanged(
                pacienteId: (int) $current->id,
                tutorId: (int) $currentTutor->id,
                anteriorTutorId: $previousPrincipal === null ? null : (int) $previousPrincipal->tutor_id,
                motivo: $reason,
                branchIds: $this->branchResolver->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $relation;
        });
    }
}
