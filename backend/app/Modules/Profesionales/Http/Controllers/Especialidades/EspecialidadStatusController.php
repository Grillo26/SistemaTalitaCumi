<?php

namespace App\Modules\Profesionales\Http\Controllers\Especialidades;

use App\Models\User;
use App\Modules\Profesionales\Actions\Especialidades\ActivateEspecialidadAction;
use App\Modules\Profesionales\Actions\Especialidades\DeactivateEspecialidadAction;
use App\Modules\Profesionales\Http\Requests\Especialidades\ChangeEspecialidadStatusRequest;
use App\Modules\Profesionales\Http\Resources\EspecialidadResource;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Support\EspecialidadAbilities;
use Illuminate\Http\JsonResponse;

/**
 * Activación y desactivación de especialidades (03/RF-E02 / 03/RN-20).
 */
final class EspecialidadStatusController
{
    public function activate(
        int $id,
        ChangeEspecialidadStatusRequest $request,
        ActivateEspecialidadAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $especialidad = $action(
            $id,
            (string) $data['motivo'],
            (int) $data['version'],
            $actor->id,
            $request->ip(),
        );

        return $this->response($request, $especialidad);
    }

    public function deactivate(
        int $id,
        ChangeEspecialidadStatusRequest $request,
        DeactivateEspecialidadAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $especialidad = $action(
            $id,
            (string) $data['motivo'],
            (int) $data['version'],
            $actor->id,
            $request->ip(),
        );

        return $this->response($request, $especialidad);
    }

    private function response(ChangeEspecialidadStatusRequest $request, Especialidad $especialidad): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $especialidad->loadCount('profesionalesActivos');

        return response()->json([
            'data' => EspecialidadResource::forActor($actor, $especialidad, $request),
            'can' => EspecialidadAbilities::collection($actor),
        ]);
    }
}
