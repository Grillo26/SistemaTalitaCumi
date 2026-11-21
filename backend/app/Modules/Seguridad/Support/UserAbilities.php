<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;

/**
 * Habilidades de la pantalla de usuarios (02/RF-R06).
 *
 * La UI solo oculta acciones; el backend revalida siempre.
 */
final class UserAbilities
{
    /**
     * @return array<string, bool>
     */
    public static function collection(User $actor): array
    {
        return [
            'create' => $actor->can(SecurityPermission::USER_CREATE),
            'export' => false,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function for(User $actor, User $target): array
    {
        $sessions = $target->sesiones_activas;

        if ($sessions === null) {
            $sessions = $target->tokens()
                ->where(static fn ($query) => $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now()))
                ->count();
        }

        return [
            'view' => $actor->can(SecurityPermission::USER_VIEW),
            'update' => $actor->can(SecurityPermission::USER_EDIT),
            'change_status' => $actor->can(SecurityPermission::USER_CHANGE_STATUS),
            'reset_password' => $actor->can(SecurityPermission::USER_RESET_PASSWORD)
                && $actor->id !== $target->id,
            'unlock' => $actor->can(SecurityPermission::USER_UNLOCK) && $target->estaBloqueado(),
            'close_sessions' => $actor->can(SecurityPermission::USER_CLOSE_SESSIONS) && $sessions > 0,
            'delete' => false,
        ];
    }
}
