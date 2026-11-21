<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\CloseUserSessionsAction;
use App\Modules\Seguridad\Http\Requests\CloseUserSessionsRequest;
use App\Modules\Seguridad\Http\Resources\UserResource;
use App\Modules\Seguridad\Queries\Usuarios\GetUserQuery;
use Illuminate\Http\JsonResponse;

/**
 * Cierre remoto de sesiones (02/RF-A09 / 02/RN-10).
 */
final class UserSessionController
{
    public function close(
        int $id,
        CloseUserSessionsRequest $request,
        GetUserQuery $getUser,
        CloseUserSessionsAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $target = $getUser($id, $actor);
        $result = $action($target, (string) $data['motivo'], $actor, $request->ip());

        return response()->json([
            'data' => UserResource::withAbilities($actor, $result['user'], $request),
            'sesiones_cerradas' => $result['sessions_closed'],
        ]);
    }
}
