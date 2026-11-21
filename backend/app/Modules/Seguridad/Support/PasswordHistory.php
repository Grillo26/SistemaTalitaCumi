<?php

namespace App\Modules\Seguridad\Support;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\PasswordPolicyViolationException;
use App\Modules\Seguridad\Models\UsuarioPasswordHistorial;
use Illuminate\Support\Facades\Hash;

/**
 * Historial de hashes de contraseña (02/RF-A02 / 02/RN-12).
 *
 * Guarda hashes previos, nunca contraseñas en claro. Se conservan los
 * últimos N-1 hashes previos; junto con la contraseña actual se cubren
 * las últimas N contraseñas.
 */
final class PasswordHistory
{
    public function assertNotReused(User $user, string $plainPassword, int $limit): void
    {
        if ($limit <= 0) {
            return;
        }

        if (Hash::check($plainPassword, (string) $user->password)) {
            throw new PasswordPolicyViolationException([
                'No podés reutilizar tu contraseña actual.',
            ]);
        }

        $previousLimit = max(0, $limit - 1);

        if ($previousLimit === 0) {
            return;
        }

        $hashes = UsuarioPasswordHistorial::query()
            ->where('usuario_id', $user->id)
            ->orderByDesc('id')
            ->limit($previousLimit)
            ->pluck('password_hash');

        foreach ($hashes as $hash) {
            if (Hash::check($plainPassword, (string) $hash)) {
                throw new PasswordPolicyViolationException([
                    'No podés reutilizar una de tus últimas contraseñas.',
                ]);
            }
        }
    }

    /**
     * Registra el hash que se está reemplazando y poda los más antiguos.
     */
    public function recordPrevious(User $user, string $hash, int $limit): void
    {
        if ($limit <= 0) {
            return;
        }

        UsuarioPasswordHistorial::query()->create([
            'usuario_id' => $user->id,
            'password_hash' => $hash,
        ]);

        $keep = max(0, $limit - 1);

        if ($keep === 0) {
            UsuarioPasswordHistorial::query()->where('usuario_id', $user->id)->delete();

            return;
        }

        $idsToKeep = UsuarioPasswordHistorial::query()
            ->where('usuario_id', $user->id)
            ->orderByDesc('id')
            ->limit($keep)
            ->pluck('id')
            ->all();

        UsuarioPasswordHistorial::query()
            ->where('usuario_id', $user->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
