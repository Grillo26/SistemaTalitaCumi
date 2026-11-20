<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\CreateBranchAction;
use App\Modules\Organizacion\Actions\RejectBranchDeletionAction;
use App\Modules\Organizacion\Actions\UpdateBranchAction;
use App\Modules\Organizacion\Http\Requests\IndexBranchRequest;
use App\Modules\Organizacion\Http\Requests\StoreBranchRequest;
use App\Modules\Organizacion\Http\Requests\UpdateBranchRequest;
use App\Modules\Organizacion\Http\Resources\BranchResource;
use App\Modules\Organizacion\Queries\GetBranchQuery;
use App\Modules\Organizacion\Queries\ListBranchesQuery;
use App\Modules\Organizacion\Support\BranchAbilities;
use App\Modules\Organizacion\Support\BranchPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de sucursales — Módulo 01 Organización y sucursales.
 *
 * RF cubiertos: RF-001, RF-002, RF-003, RF-004, RF-005, RF-006, RF-008
 * (rechazo explícito), RF-013 y RF-014 (permisos).
 * RN aplicadas: RN-001, RN-002, RN-004, RN-005, RN-006 (parcial) y RN-009.
 *
 * Pendiente del módulo 02: RF-009, RF-010 y el alcance real de RF-004,
 * RF-005, RF-006 y RF-014.
 */
final class BranchController
{
    public function index(IndexBranchRequest $request, ListBranchesQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            estado: $data['estado'] ?? null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actorId: $user->id,
            hasGlobalScope: $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => BranchResource::collection($paginator->items())->resolve($request),
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
            'can' => BranchAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetBranchQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $branch = $query($id, $user->id, $user->can(BranchPermission::GLOBAL_SCOPE));

        return response()->json([
            'data' => (new BranchResource($branch))->resolve($request),
            'can' => BranchAbilities::for($user),
        ]);
    }

    public function store(StoreBranchRequest $request, CreateBranchAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $branch = $action($request->validated(), $user->id);

        return response()->json([
            'data' => (new BranchResource($branch))->resolve($request),
            'can' => BranchAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateBranchRequest $request, UpdateBranchAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $branch = $action(
            branchId: $id,
            attributes: $data,
            expectedVersion: $expectedVersion,
            actorId: $user->id,
            hasGlobalScope: $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new BranchResource($branch))->resolve($request),
            'can' => BranchAbilities::for($user),
        ]);
    }

    /**
     * Rechaza siempre la eliminación física (CA-007).
     */
    public function destroy(int $id, Request $request, RejectBranchDeletionAction $action): never
    {
        /** @var User $user */
        $user = $request->user();

        $action($id, $user->id, $user->can(BranchPermission::GLOBAL_SCOPE));
    }
}
