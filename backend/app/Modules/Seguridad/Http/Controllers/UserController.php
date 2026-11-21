<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\CreateUserAction;
use App\Modules\Seguridad\Actions\Usuarios\RejectUserDeletionAction;
use App\Modules\Seguridad\Actions\Usuarios\UpdateUserAction;
use App\Modules\Seguridad\Http\Requests\IndexUserRequest;
use App\Modules\Seguridad\Http\Requests\StoreUserRequest;
use App\Modules\Seguridad\Http\Requests\UpdateUserRequest;
use App\Modules\Seguridad\Http\Resources\UserResource;
use App\Modules\Seguridad\Queries\Usuarios\GetUserFormOptionsQuery;
use App\Modules\Seguridad\Queries\Usuarios\GetUserQuery;
use App\Modules\Seguridad\Queries\Usuarios\ListUsersQuery;
use App\Modules\Seguridad\Support\UserAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de usuarios — módulo 02, Fase 2.
 *
 * 02/RF-U01–U08, 02/RF-U11, 02/RF-U16.
 */
final class UserController
{
    public function index(IndexUserRequest $request, ListUsersQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            roleId: isset($data['rol_id']) ? (int) $data['rol_id'] : null,
            branchId: isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            estado: $data['estado'] ?? null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
        );

        $items = collect($paginator->items())
            ->map(static fn (User $user): array => UserResource::withAbilities($actor, $user, $request))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
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
            'can' => UserAbilities::collection($actor),
        ]);
    }

    public function options(Request $request, GetUserFormOptionsQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json(['data' => $query($actor)]);
    }

    public function show(int $id, Request $request, GetUserQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $user = $query($id, $actor);

        return response()->json([
            'data' => UserResource::withAbilities($actor, $user, $request),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $result = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => UserResource::withAbilities($actor, $result['user'], $request),
            'password_temporal' => $result['password_temporal'],
            'message' => 'Usuario creado. La contraseña temporal se muestra una sola vez.',
        ], 201);
    }

    public function update(
        int $id,
        UpdateUserRequest $request,
        GetUserQuery $getUser,
        UpdateUserAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $target = $getUser($id, $actor);
        $user = $action($target, $data, $expectedVersion, $actor, $request->ip());

        return response()->json([
            'data' => UserResource::withAbilities($actor, $user, $request),
        ]);
    }

    /**
     * Rechaza siempre la eliminación física (02/RF-U11).
     */
    public function destroy(int $id, Request $request, RejectUserDeletionAction $action): never
    {
        /** @var User $actor */
        $actor = $request->user();

        $action($id, $actor);
    }
}
