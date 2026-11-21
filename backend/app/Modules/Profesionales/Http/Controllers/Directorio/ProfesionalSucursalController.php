<?php

namespace App\Modules\Profesionales\Http\Controllers\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Actions\Directorio\ActivateProfesionalSucursalAction;
use App\Modules\Profesionales\Actions\Directorio\AssignProfesionalSucursalAction;
use App\Modules\Profesionales\Actions\Directorio\DeactivateProfesionalSucursalAction;
use App\Modules\Profesionales\Actions\Directorio\RejectProfesionalSucursalDeletionAction;
use App\Modules\Profesionales\Http\Requests\Directorio\ChangeProfesionalSucursalStatusRequest;
use App\Modules\Profesionales\Http\Requests\Directorio\StoreProfesionalSucursalRequest;
use App\Modules\Profesionales\Http\Resources\ProfesionalSucursalResource;
use App\Modules\Profesionales\Queries\Directorio\GetProfesionalQuery;
use App\Modules\Profesionales\Queries\Directorio\ListProfesionalSucursalesQuery;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sucursales de trabajo del profesional (03/RF-E06, 03/RF-E07).
 */
final class ProfesionalSucursalController
{
    public function index(
        int $id,
        Request $request,
        GetProfesionalQuery $getProfesional,
        ListProfesionalSucursalesQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $profesional = $getProfesional($id, $actor);

        return response()->json([
            'data' => $query($profesional, $actor),
            'can' => [
                'administrar' => $actor->can(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN),
            ],
        ]);
    }

    public function store(
        int $id,
        StoreProfesionalSucursalRequest $request,
        GetProfesionalQuery $getProfesional,
        AssignProfesionalSucursalAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $relation = $action(
            $getProfesional($id, $actor),
            (int) $data['sucursal_id'],
            $actor,
            $request->ip(),
        );

        return $this->relationResponse($request, $relation);
    }

    public function activate(
        int $id,
        int $sucursalId,
        ChangeProfesionalSucursalStatusRequest $request,
        GetProfesionalQuery $getProfesional,
        ActivateProfesionalSucursalAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $relation = $action(
            $getProfesional($id, $actor),
            $sucursalId,
            (string) $data['motivo'],
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return $this->relationResponse($request, $relation);
    }

    public function deactivate(
        int $id,
        int $sucursalId,
        ChangeProfesionalSucursalStatusRequest $request,
        GetProfesionalQuery $getProfesional,
        DeactivateProfesionalSucursalAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $relation = $action(
            $getProfesional($id, $actor),
            $sucursalId,
            (string) $data['motivo'],
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return $this->relationResponse($request, $relation);
    }

    public function destroy(
        int $id,
        int $sucursalId,
        Request $request,
        GetProfesionalQuery $getProfesional,
        RejectProfesionalSucursalDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();

        $action($getProfesional($id, $actor), $sucursalId);
    }

    private function relationResponse(Request $request, $relation): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new ProfesionalSucursalResource($relation))->resolve($request),
            'can' => [
                'administrar' => $actor->can(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN),
            ],
        ]);
    }
}
