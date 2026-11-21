<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Autenticacion\ChangePasswordAction;
use App\Modules\Seguridad\Actions\Autenticacion\LoginAction;
use App\Modules\Seguridad\Actions\Autenticacion\LogoutAction;
use App\Modules\Seguridad\Http\Requests\ChangePasswordRequest;
use App\Modules\Seguridad\Http\Requests\LoginRequest;
use App\Modules\Seguridad\Http\Resources\AuthenticatedUserResource;
use App\Modules\Seguridad\Http\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Autenticación y sesión propia — módulo 02, Fase 1.
 *
 * 02/RF-A01, 02/RF-A03, 02/RF-A08, 02/RF-A11, 02/RF-A12, 02/RF-A16.
 */
final class AuthController
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $data = $request->validated();

        $result = $action(
            identifier: (string) $data['identificador'],
            password: (string) $data['password'],
            ip: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'data' => (new SessionResource($result))->resolve($request),
        ]);
    }

    public function logout(Request $request, LogoutAction $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $action($user, $request->ip());

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new AuthenticatedUserResource($user))->resolve($request),
        ]);
    }

    public function changePassword(
        ChangePasswordRequest $request,
        ChangePasswordAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $action(
            user: $user,
            currentPassword: (string) $data['current_password'],
            newPassword: (string) $data['password'],
            ip: $request->ip(),
        );

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
