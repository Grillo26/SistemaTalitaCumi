<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteTutorNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
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
        private readonly PacienteBranchResolver $branchResolver,
    ) {}

    public function __invoke(
        Paciente $paciente,
        Tutor $tutor,
        User $actor,
        ?string $ip = null,
    ): PacienteTutor {
        $this->pacienteScope->assertCanAccess($actor, $paciente);
        $this->tutorScope->assertCanAssociate($actor, $tutor);
        $this->principalRule->assertCanBePrincipal((bool) $tutor->activo);

        return DB::transaction(function () use ($paciente, $tutor, $actor, $ip): PacienteTutor {
            $current = Paciente::query()->whereKey($paciente->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new PacienteNotFoundException((int) $paciente->id);
            }

            $relation = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $tutor->id)
                ->first();

            if ($relation === null) {
                throw new PacienteTutorNotFoundException((int) $current->id, (int) $tutor->id);
            }

            $previousPrincipal = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('es_principal', true)
                ->first();

            if ($previousPrincipal !== null && (int) $previousPrincipal->tutor_id === (int) $tutor->id) {
                return $relation;
            }

            PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->update(['es_principal' => false]);

            // La tabla tiene PK compuesta; se actualiza por query y no por
            // `save()` del modelo (Eloquent asumiría una columna `id`).
            PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $tutor->id)
                ->update(['es_principal' => true]);

            $relation = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $tutor->id)
                ->firstOrFail();

            event(new PacienteTutorPrincipalChanged(
                pacienteId: (int) $current->id,
                tutorId: (int) $tutor->id,
                anteriorTutorId: $previousPrincipal === null ? null : (int) $previousPrincipal->tutor_id,
                branchIds: $this->branchResolver->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $relation;
        });
    }
}
