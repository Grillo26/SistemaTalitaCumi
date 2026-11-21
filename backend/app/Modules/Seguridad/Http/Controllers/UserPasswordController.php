<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Usuarios\ResetUserPasswordAction;
use App\Modules\Seguridad\Http\Resources\UserResource;
use App\Modules\Seguridad\Queries\Usuarios\GetUserQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reseteo de contraseña con temporal mostrada una vez (02/RF-U10 / 02/RF-A04).
 */
final class UserPasswordController
{
    public function reset(
        int $id,
        Request $request,
        GetUserQuery $getUser,
        ResetUserPasswordAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $target = $getUser($id, $actor);
        $result = $action($target, $actor, $request->ip());

        return response()->json([
            'data' => UserResource::withAbilities($actor, $result['user'], $request),
            'password_temporal' => $result['password_temporal'],
            'sessions_invalidated' => $result['sessions_invalidated'],
            'message' => 'Contraseña reseteada. La contraseña temporal se muestra una sola vez.',
        ]);
    }
}
