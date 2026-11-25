<?php

namespace App\Modules\Pacientes\Http\Controllers\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Actions\Tutores\AssociateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\DissociateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\SetPrincipalTutorAction;
use App\Modules\Pacientes\Http\Requests\Tutores\AssociateTutorRequest;
use App\Modules\Pacientes\Http\Requests\Tutores\SetPrincipalTutorRequest;
use App\Modules\Pacientes\Http\Resources\TutorResource;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Queries\Tutores\GetTutorQuery;
use App\Modules\Pacientes\Queries\Tutores\ListPacienteTutoresQuery;
use App\Modules\Pacientes\Support\PacienteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tutores de un paciente (04.02/RF-004–RF-006).
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

        $items = $query($paciente, $actor)
            ->map(static fn (PacienteTutor $relacion): array => TutorResource::forPaciente(
                $actor,
                $relacion,
                $request,
            ))
            ->filter(static fn (array $tutor): bool => $tutor !== [])
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
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
        $relation = $action(
            $paciente,
            $tutor,
            (bool) ($data['es_principal'] ?? false),
            $actor,
            $request->ip(),
            isset($data['parentesco_id']) ? (int) $data['parentesco_id'] : null,
            null,
            $data['motivo'] ?? null,
        );
        $relation->load(['tutor.parentescoCatalogo', 'tutor.sucursalRegistro', 'parentesco']);

        return response()->json([
            'data' => TutorResource::forPaciente($actor, $relation, $request),
            'can' => PacienteAbilities::for($actor, $paciente),
        ], 201);
    }

    public function setPrincipal(
        int $id,
        int $tutorId,
        SetPrincipalTutorRequest $request,
        GetPacienteQuery $getPaciente,
        GetTutorQuery $getTutor,
        SetPrincipalTutorAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $tutor = $getTutor($tutorId, $actor);
        $relation = $action(
            $paciente,
            $tutor,
            $actor,
            $request->ip(),
            $request->validated()['motivo'] ?? null,
        );
        $relation->load(['tutor.parentescoCatalogo', 'tutor.sucursalRegistro', 'parentesco']);

        return response()->json([
            'data' => TutorResource::forPaciente($actor, $relation, $request),
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
