<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\UnlockUserAction;
use App\Modules\Seguridad\Http\Requests\UnlockUserRequest;
use App\Modules\Seguridad\Http\Resources\UserResource;
use App\Modules\Seguridad\Queries\Usuarios\GetUserQuery;
use Illuminate\Http\JsonResponse;

/**
 * Desbloqueo manual de cuenta (02/RF-A06 / 02/RN-13).
 */
final class UserUnlockController
{
    public function __invoke(
        int $id,
        UnlockUserRequest $request,
        GetUserQuery $getUser,
        UnlockUserAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $target = $getUser($id, $actor);
        $user = $action(
            target: $target,
            reason: (string) $data['motivo'],
            expectedVersion: (int) $data['version'],
            actor: $actor,
            ip: $request->ip(),
        );

        return response()->json([
            'data' => UserResource::withAbilities($actor, $user, $request),
        ]);
    }
}
