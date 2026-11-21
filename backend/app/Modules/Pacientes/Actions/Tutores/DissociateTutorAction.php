<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorDissociated;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteTutorNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorPrincipalRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use Illuminate\Support\Facades\DB;

/**
 * Desasociar tutor (04.02/RF-006).
 *
 * Un paciente activo no puede quedar sin tutor principal.
 */
final class DissociateTutorAction
{
    public function __construct(
        private readonly PacienteAccessScope $pacienteScope,
        private readonly TutorPrincipalRule $principalRule,
        private readonly PacienteBranchResolver $branchResolver,
    ) {}

    public function __invoke(Paciente $paciente, Tutor $tutor, User $actor, ?string $ip = null): void
    {
        $this->pacienteScope->assertCanAccess($actor, $paciente);

        DB::transaction(function () use ($paciente, $tutor, $actor, $ip): void {
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

            $this->principalRule->assertCanDissociate(
                (bool) $relation->es_principal,
                $current->estado === EstadoPaciente::ACTIVO,
            );

            $eraPrincipal = (bool) $relation->es_principal;

            // La tabla tiene PK compuesta; se elimina por query y no por
            // `delete()` del modelo (Eloquent asumiría una columna `id`).
            PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $tutor->id)
                ->delete();

            event(new PacienteTutorDissociated(
                pacienteId: (int) $current->id,
                tutorId: (int) $tutor->id,
                eraPrincipal: $eraPrincipal,
                branchIds: $this->branchResolver->for($current),
                actorId: $actor->id,
                ip: $ip,
            ));
        });
    }
}
