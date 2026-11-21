<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\ActivateUserAction;
use App\Modules\Seguridad\Actions\Usuarios\DeactivateUserAction;
use App\Modules\Seguridad\Http\Requests\ChangeUserStatusRequest;
use App\Modules\Seguridad\Http\Resources\UserResource;
use App\Modules\Seguridad\Queries\Usuarios\GetUserQuery;
use Illuminate\Http\JsonResponse;

/**
 * Activación y desactivación de usuarios (02/RF-U09 / 02/RN-10).
 */
final class UserStatusController
{
    public function activate(
        int $id,
        ChangeUserStatusRequest $request,
        GetUserQuery $getUser,
        ActivateUserAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $target = $getUser($id, $actor);
        $user = $action(
            target: $target,
            reason: $data['motivo'] ?? null,
            expectedVersion: (int) $data['version'],
            actor: $actor,
            ip: $request->ip(),
        );

        return response()->json([
            'data' => UserResource::withAbilities($actor, $user, $request),
        ]);
    }

    public function deactivate(
        int $id,
        ChangeUserStatusRequest $request,
        GetUserQuery $getUser,
        DeactivateUserAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $target = $getUser($id, $actor);
        $user = $action(
            target: $target,
            reason: $data['motivo'] ?? null,
            expectedVersion: (int) $data['version'],
            actor: $actor,
            ip: $request->ip(),
        );

        return response()->json([
            'data' => UserResource::withAbilities($actor, $user, $request),
        ]);
    }
}
