<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\ActivateAmbienteAction;
use App\Modules\Organizacion\Actions\CreateAmbienteAction;
use App\Modules\Organizacion\Actions\DeactivateAmbienteAction;
use App\Modules\Organizacion\Actions\RejectAmbienteDeletionAction;
use App\Modules\Organizacion\Actions\UpdateAmbienteAction;
use App\Modules\Organizacion\Http\Requests\ChangeSpaceStatusRequest;
use App\Modules\Organizacion\Http\Requests\IndexAmbienteRequest;
use App\Modules\Organizacion\Http\Requests\StoreAmbienteRequest;
use App\Modules\Organizacion\Http\Requests\UpdateAmbienteRequest;
use App\Modules\Organizacion\Http\Resources\AmbienteResource;
use App\Modules\Organizacion\Queries\GetAmbienteQuery;
use App\Modules\Organizacion\Queries\ListAmbientesQuery;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Organizacion\Support\SalasAmbientesAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ambientes — Submódulo 02 Salas y ambientes.
 *
 * RF-006, RF-007, RF-008, RF-009, RF-010, RF-011, RF-012, RF-015 y RF-017.
 * RN aplicadas: RN-004, RN-006 y RN-010 (alcance módulo 02).
 */
final class AmbienteController
{
    public function index(IndexAmbienteRequest $request, ListAmbientesQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            sucursalId: isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            uso: $data['uso'] ?? null,
            estado: $data['estado'] ?? null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actorId: $user->id,
            hasGlobalScope: $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => AmbienteResource::collection($paginator->items())->resolve($request),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetAmbienteQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ambiente = $query($id, $user->id, $user->can(BranchPermission::GLOBAL_SCOPE));

        return response()->json([
            'data' => (new AmbienteResource($ambiente))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function store(StoreAmbienteRequest $request, CreateAmbienteAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ambiente = $action(
            $request->validated(),
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new AmbienteResource($ambiente))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateAmbienteRequest $request, UpdateAmbienteAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $ambiente = $action(
            $id,
            $data,
            $version,
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new AmbienteResource($ambiente))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeSpaceStatusRequest $request, ActivateAmbienteAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $ambiente = $action(
            $id,
            (int) $data['version'],
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new AmbienteResource($ambiente))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function deactivate(int $id, ChangeSpaceStatusRequest $request, DeactivateAmbienteAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $ambiente = $action(
            $id,
            $data['motivo'] ?? null,
            (int) $data['version'],
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new AmbienteResource($ambiente))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function destroy(int $id, Request $request, RejectAmbienteDeletionAction $action): never
    {
        /** @var User $user */
        $user = $request->user();
        $action($id, $user->id, $user->can(BranchPermission::GLOBAL_SCOPE));
    }
}
