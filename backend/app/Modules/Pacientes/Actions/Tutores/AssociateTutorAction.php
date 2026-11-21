<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorAssociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteTutorDuplicatedException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorPrincipalRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Illuminate\Support\Facades\DB;

/**
 * Asociar tutor a paciente (04.01/RF-010, 04.02/RF-004).
 */
final class AssociateTutorAction
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
        bool $esPrincipal,
        User $actor,
        ?string $ip = null,
    ): PacienteTutor {
        $this->pacienteScope->assertCanAccess($actor, $paciente);
        $this->tutorScope->assertCanAssociate($actor, $tutor);
        $this->principalRule->assertCanBePrincipal((bool) $tutor->activo);

        return DB::transaction(function () use ($paciente, $tutor, $esPrincipal, $actor, $ip): PacienteTutor {
            $current = Paciente::query()->whereKey($paciente->id)->lockForUpdate()->first();

            if ($current === null) {
                throw new PacienteNotFoundException((int) $paciente->id);
            }

            $existing = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $tutor->id)
                ->first();

            if ($existing !== null) {
                throw new PacienteTutorDuplicatedException;
            }

            $previousPrincipal = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('es_principal', true)
                ->first();

            $principal = $esPrincipal || $previousPrincipal === null;

            if ($principal && $previousPrincipal !== null) {
                PacienteTutor::query()
                    ->where('paciente_id', $current->id)
                    ->update(['es_principal' => false]);
            }

            $relation = PacienteTutor::query()->create([
                'paciente_id' => $current->id,
                'tutor_id' => $tutor->id,
                'es_principal' => $principal,
            ]);

            $branchIds = $this->branchResolver->for($current);

            event(new PacienteTutorAssociated(
                pacienteId: (int) $current->id,
                tutorId: (int) $tutor->id,
                esPrincipal: $principal,
                branchIds: $branchIds,
                actorId: $actor->id,
                ip: $ip,
            ));

            if ($principal && $previousPrincipal !== null) {
                event(new PacienteTutorPrincipalChanged(
                    pacienteId: (int) $current->id,
                    tutorId: (int) $tutor->id,
                    anteriorTutorId: (int) $previousPrincipal->tutor_id,
                    branchIds: $branchIds,
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $relation;
        });
    }
}
