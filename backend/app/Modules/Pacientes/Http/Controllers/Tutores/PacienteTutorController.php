<?php

namespace App\Modules\Pacientes\Http\Controllers\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Actions\Tutores\AssociateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\DissociateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\SetPrincipalTutorAction;
use App\Modules\Pacientes\Http\Requests\Tutores\AssociateTutorRequest;
use App\Modules\Pacientes\Http\Resources\TutorResource;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Queries\Tutores\GetTutorQuery;
use App\Modules\Pacientes\Queries\Tutores\ListPacienteTutoresQuery;
use App\Modules\Pacientes\Support\PacienteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tutores de un paciente (04.01/RF-010, 04.02/RF-004–RF-006).
 */
final class PacienteTutorController
{
    public function index(
        int $id,
        Request $request,
        GetPacienteQuery $getPaciente,
        ListPacienteTutoresQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);

        return response()->json([
            'data' => $query($paciente, $actor),
            'can' => PacienteAbilities::for($actor, $paciente),
        ]);
    }

    public function store(
        int $id,
        AssociateTutorRequest $request,
        GetPacienteQuery $getPaciente,
        GetTutorQuery $getTutor,
        AssociateTutorAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paciente = $getPaciente($id, $actor);
        $tutor = $getTutor((int) $data['tutor_id'], $actor);
        $relation = $action($paciente, $tutor, (bool) ($data['es_principal'] ?? false), $actor, $request->ip());

        return response()->json([
            'data' => [
                'paciente_id' => (int) $relation->paciente_id,
                'tutor_id' => (int) $relation->tutor_id,
                'es_principal' => (bool) $relation->es_principal,
                'tutor' => TutorResource::for($actor, $tutor, $request),
            ],
            'can' => PacienteAbilities::for($actor, $paciente),
        ], 201);
    }

    public function setPrincipal(
        int $id,
        int $tutorId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetTutorQuery $getTutor,
        SetPrincipalTutorAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $tutor = $getTutor($tutorId, $actor);
        $relation = $action($paciente, $tutor, $actor, $request->ip());

        return response()->json([
            'data' => [
                'paciente_id' => (int) $relation->paciente_id,
                'tutor_id' => (int) $relation->tutor_id,
                'es_principal' => (bool) $relation->es_principal,
                'tutor' => TutorResource::for($actor, $tutor, $request),
            ],
            'can' => PacienteAbilities::for($actor, $paciente),
        ]);
    }

    public function destroy(
        int $id,
        int $tutorId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetTutorQuery $getTutor,
        DissociateTutorAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $tutor = $getTutor($tutorId, $actor);
        $action($paciente, $tutor, $actor, $request->ip());

        return response()->json([
            'message' => 'Tutor desasociado del paciente.',
            'can' => PacienteAbilities::for($actor, $paciente),
        ]);
    }
}
