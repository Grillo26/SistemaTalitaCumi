<?php

namespace App\Modules\Seguridad\Actions\Autenticacion;

use App\Models\User;
use App\Modules\Seguridad\Events\UserLoggedOut;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Cierre de sesión local (02/RF-A08, 02/RF-A12).
 *
 * El token se elimina y la bitácora se escribe en la misma transacción.
 */
final class LogoutAction
{
    public function __invoke(User $user, ?string $ip): void
    {
        DB::transaction(function () use ($user, $ip): void {
            $token = $user->currentAccessToken();
            $tokenId = $token instanceof PersonalAccessToken && $token->exists
                ? (int) $token->getKey()
                : null;

            if ($token instanceof PersonalAccessToken && $token->exists) {
                $token->delete();
            }

            event(new UserLoggedOut(
                userId: $user->id,
                tokenId: $tokenId,
                ip: $ip,
            ));
        });
    }
}
