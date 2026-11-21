<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorAssociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Exceptions\PacienteTutorDuplicatedException;
use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use App\Modules\Pacientes\Exceptions\TutorNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorPrincipalChangeRule;
use App\Modules\Pacientes\Rules\TutorPrincipalRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use App\Modules\Pacientes\Support\PacienteBranchResolver;
use App\Modules\Pacientes\Support\ParentescoResolver;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Illuminate\Support\Facades\DB;

/**
 * Asociar tutor a paciente (04.02/RF-004).
 */
final class AssociateTutorAction
{
    public function __construct(
        private readonly PacienteAccessScope $pacienteScope,
        private readonly TutorAccessScope $tutorScope,
        private readonly TutorPrincipalRule $principalRule,
        private readonly TutorPrincipalChangeRule $principalChangeRule,
        private readonly ParentescoResolver $parentescos,
        private readonly PacienteBranchResolver $branchResolver,
    ) {}

    public function __invoke(
        Paciente $paciente,
        Tutor $tutor,
        bool $esPrincipal,
        User $actor,
        ?string $ip = null,
        ?int $parentescoId = null,
        ?string $parentescoNombre = null,
        ?string $motivo = null,
    ): PacienteTutor {
        $this->pacienteScope->assertCanAccess($actor, $paciente);
        $this->tutorScope->assertCanAssociate($actor, $tutor);
        $this->principalRule->assertCanBePrincipal((bool) $tutor->activo);

        if ($parentescoId !== null) {
            $parentesco = $this->parentescos->resolve($parentescoId, null);
        } elseif ($parentescoNombre !== null && trim($parentescoNombre) !== '') {
            $parentesco = $this->parentescos->resolve(null, $parentescoNombre);
        } elseif ($tutor->parentesco_id !== null) {
            $parentesco = $this->parentescos->resolve((int) $tutor->parentesco_id, null);
        } else {
            $parentesco = $this->parentescos->resolve(null, null);
        }

        return DB::transaction(function () use ($paciente, $tutor, $esPrincipal, $actor, $ip, $parentesco, $motivo): PacienteTutor {
            // Lock del tutor primero para serializar con el cambio de estado.
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

            $existing = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('tutor_id', $currentTutor->id)
                ->first();

            if ($existing !== null) {
                throw new PacienteTutorDuplicatedException;
            }

            $previousPrincipal = PacienteTutor::query()
                ->where('paciente_id', $current->id)
                ->where('es_principal', true)
                ->first();

            $principal = $esPrincipal || $previousPrincipal === null;
            $reason = $this->principalChangeRule->reason(
                $principal && $previousPrincipal !== null,
                $motivo,
            );

            if ($principal && $previousPrincipal !== null) {
                PacienteTutor::query()
                    ->where('paciente_id', $current->id)
                    ->update(['es_principal' => false]);
            }

            $relation = PacienteTutor::query()->create([
                'paciente_id' => $current->id,
                'tutor_id' => $currentTutor->id,
                'parentesco_id' => $parentesco->id,
                'es_principal' => $principal,
            ]);

            $branchIds = $this->branchResolver->for($current);

            event(new PacienteTutorAssociated(
                pacienteId: (int) $current->id,
                tutorId: (int) $currentTutor->id,
                parentescoId: (int) $parentesco->id,
                esPrincipal: $principal,
                branchIds: $branchIds,
                actorId: $actor->id,
                ip: $ip,
            ));

            if ($principal && $previousPrincipal !== null) {
                event(new PacienteTutorPrincipalChanged(
                    pacienteId: (int) $current->id,
                    tutorId: (int) $currentTutor->id,
                    anteriorTutorId: (int) $previousPrincipal->tutor_id,
                    motivo: $reason,
                    branchIds: $branchIds,
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $relation;
        });
    }
}
