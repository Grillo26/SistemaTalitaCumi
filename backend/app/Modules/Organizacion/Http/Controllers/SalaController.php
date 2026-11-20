<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\ActivateSalaAction;
use App\Modules\Organizacion\Actions\CreateSalaAction;
use App\Modules\Organizacion\Actions\DeactivateSalaAction;
use App\Modules\Organizacion\Actions\RejectSalaDeletionAction;
use App\Modules\Organizacion\Actions\UpdateSalaAction;
use App\Modules\Organizacion\Http\Requests\ChangeSpaceStatusRequest;
use App\Modules\Organizacion\Http\Requests\IndexSalaRequest;
use App\Modules\Organizacion\Http\Requests\StoreSalaRequest;
use App\Modules\Organizacion\Http\Requests\UpdateSalaRequest;
use App\Modules\Organizacion\Http\Resources\SalaResource;
use App\Modules\Organizacion\Queries\GetSalaQuery;
use App\Modules\Organizacion\Queries\ListSalasQuery;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Organizacion\Support\SalasAmbientesAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Salas grupales — Submódulo 02 Salas y ambientes.
 *
 * RF-004, RF-005, RF-008, RF-009, RF-010, RF-011, RF-012, RF-015 y RF-017.
 * RN aplicadas: RN-001, RN-002, RN-004, RN-006 y RN-010 (alcance módulo 02).
 */
final class SalaController
{
    public function index(IndexSalaRequest $request, ListSalasQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            sucursalId: isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            tipoSalaId: isset($data['tipo_sala_id']) ? (int) $data['tipo_sala_id'] : null,
            turnoCatalogoId: isset($data['turno_catalogo_id']) ? (int) $data['turno_catalogo_id'] : null,
            estado: $data['estado'] ?? null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actorId: $user->id,
            hasGlobalScope: $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => SalaResource::collection($paginator->items())->resolve($request),
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

    public function show(int $id, Request $request, GetSalaQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $sala = $query($id, $user->id, $user->can(BranchPermission::GLOBAL_SCOPE));

        return response()->json([
            'data' => (new SalaResource($sala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function store(StoreSalaRequest $request, CreateSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $sala = $action(
            $request->validated(),
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new SalaResource($sala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateSalaRequest $request, UpdateSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $sala = $action(
            $id,
            $data,
            $version,
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new SalaResource($sala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeSpaceStatusRequest $request, ActivateSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $sala = $action(
            $id,
            (int) $data['version'],
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new SalaResource($sala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function deactivate(int $id, ChangeSpaceStatusRequest $request, DeactivateSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $sala = $action(
            $id,
            $data['motivo'] ?? null,
            (int) $data['version'],
            $user->id,
            $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new SalaResource($sala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function destroy(int $id, Request $request, RejectSalaDeletionAction $action): never
    {
        /** @var User $user */
        $user = $request->user();
        $action($id, $user->id, $user->can(BranchPermission::GLOBAL_SCOPE));
    }
}
