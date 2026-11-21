<?php

namespace App\Modules\Profesionales\Http\Controllers\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Actions\Directorio\ActivateProfesionalAction;
use App\Modules\Profesionales\Actions\Directorio\DeactivateProfesionalAction;
use App\Modules\Profesionales\Http\Requests\Directorio\ChangeProfesionalStatusRequest;
use App\Modules\Profesionales\Http\Resources\ProfesionalResource;
use App\Modules\Profesionales\Queries\Directorio\GetProfesionalQuery;
use App\Modules\Profesionales\Support\ProfesionalAbilities;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;
use Illuminate\Http\JsonResponse;

/**
 * Activación y desactivación de profesionales (03/RF-D07 / 03/RN-20).
 */
final class ProfesionalStatusController
{
    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function activate(
        int $id,
        ChangeProfesionalStatusRequest $request,
        GetProfesionalQuery $getProfesional,
        ActivateProfesionalAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $profesional = $action(
            $getProfesional($id, $actor),
            (string) $data['motivo'],
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return $this->response($request, $profesional);
    }

    public function deactivate(
        int $id,
        ChangeProfesionalStatusRequest $request,
        GetProfesionalQuery $getProfesional,
        DeactivateProfesionalAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $profesional = $action(
            $getProfesional($id, $actor),
            (string) $data['motivo'],
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return $this->response($request, $profesional);
    }

    private function response(ChangeProfesionalStatusRequest $request, $profesional): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $profesional->load(['relacionesEspecialidad.especialidad', 'relacionesSucursal.sucursal']);

        return response()->json([
            'data' => ProfesionalResource::forActor(
                $actor,
                $profesional,
                $request,
                $this->scope->accessibleBranchIds($actor),
            ),
            'can' => ProfesionalAbilities::collection($actor),
        ]);
    }
}
