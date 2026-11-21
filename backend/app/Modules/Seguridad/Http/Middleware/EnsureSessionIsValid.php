<?php

namespace App\Modules\Seguridad\Http\Middleware;

use App\Modules\Seguridad\Events\UserSessionExpired;
use App\Modules\Seguridad\Exceptions\PasswordChangeRequiredException;
use App\Modules\Seguridad\Exceptions\SessionExpiredException;
use App\Modules\Seguridad\Support\SecurityParameters;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vigila la sesión autenticada (02/RF-A07, 02/RF-A10, 02/RF-A13 / 02/RN-14).
 *
 * Debe ejecutarse después de `auth:sanctum`. Comprueba usuario activo,
 * inactividad y cambio forzado de contraseña, y actualiza `last_used_at`.
 * Sanctum tiene `last_used_at` automático desactivado para que esta
 * comprobación compare contra el valor anterior.
 */
final class EnsureSessionIsValid
{
    public function __construct(private readonly SecurityParameters $parameters) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->activo === false) {
            $this->revokeCurrentToken($user);
            throw new AuthenticationException('Sesión no válida.');
        }

        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->exists) {
            $inactivity = $this->parameters->sessionInactivityMinutes();

            if ($token->last_used_at !== null
                && $token->last_used_at->lt(now()->subMinutes($inactivity))) {
                $tokenId = (int) $token->getKey();
                $token->delete();

                event(new UserSessionExpired(
                    userId: $user->id,
                    tokenId: $tokenId,
                    ip: $request->ip(),
                ));

                throw new SessionExpiredException;
            }

            $token->forceFill(['last_used_at' => now()])->save();
        }

        if ($user->debe_cambiar_password && ! $this->isPasswordChangeAllowed($request)) {
            throw new PasswordChangeRequiredException;
        }

        return $next($request);
    }

    private function revokeCurrentToken(mixed $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->exists) {
            $token->delete();
        }
    }

    private function isPasswordChangeAllowed(Request $request): bool
    {
        /** @var list<string> $paths */
        $paths = config('seguridad.rutas_sin_cambio_password', [
            'api/auth/me',
            'api/auth/logout',
            'api/auth/cambiar-password',
        ]);

        return $request->is(...$paths);
    }
}
