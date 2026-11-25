<?php

namespace App\Modules\Pacientes\Http\Controllers\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Actions\Tutores\ChangeTutorStatusAction;
use App\Modules\Pacientes\Actions\Tutores\RejectTutorDeletionAction;
use App\Modules\Pacientes\Http\Requests\Tutores\ChangeTutorStatusRequest;
use App\Modules\Pacientes\Http\Resources\TutorResource;
use App\Modules\Pacientes\Queries\Tutores\GetTutorQuery;
use App\Modules\Pacientes\Support\TutorAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Activación, desactivación y rechazo de eliminación de tutores (04.02/RF-010, RF-011).
 */
final class TutorStatusController
{
    public function changeStatus(
        int $id,
        ChangeTutorStatusRequest $request,
        GetTutorQuery $getTutor,
        ChangeTutorStatusAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $tutor = $action(
            $getTutor($id, $actor),
            (bool) $data['activo'],
            $data['motivo'] ?? null,
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => TutorResource::forFicha($actor, $tutor, $request),
            'can' => TutorAbilities::collection($actor),
        ]);
    }

    /**
     * DELETE siempre 409: los tutores se desactivan, no se eliminan.
     */
    public function destroy(
        int $id,
        Request $request,
        GetTutorQuery $getTutor,
        RejectTutorDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();

        $getTutor($id, $actor);
        $action();
    }
}
