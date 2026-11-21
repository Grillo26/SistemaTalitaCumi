<?php

namespace App\Modules\Seguridad\Actions\Autenticacion;

use App\Models\User;
use App\Modules\Seguridad\Events\UserPasswordChanged;
use App\Modules\Seguridad\Exceptions\CurrentPasswordInvalidException;
use App\Modules\Seguridad\Exceptions\PasswordPolicyViolationException;
use App\Modules\Seguridad\Support\PasswordHistory;
use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\SecurityParameters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Cambio de contraseña por el propio usuario (02/RF-A02, 02/RF-A03 / 02/RN-12).
 *
 * Valida la contraseña actual, la política y el historial. Invalida las
 * demás sesiones y registra el evento sin guardar hashes en bitácora.
 */
final class ChangePasswordAction
{
    public function __construct(
        private readonly SecurityParameters $parameters,
        private readonly PasswordHistory $history,
    ) {}

    public function __invoke(
        User $user,
        string $currentPassword,
        string $newPassword,
        ?string $ip,
    ): void {
        $policyConfig = $this->parameters->passwordPolicy();

        if (! Hash::check($currentPassword, (string) $user->password)) {
            throw new CurrentPasswordInvalidException;
        }

        $errors = (new PasswordPolicy($policyConfig))->validate($newPassword);

        if ($errors !== []) {
            throw new PasswordPolicyViolationException($errors);
        }

        $this->history->assertNotReused($user, $newPassword, $policyConfig->historyCount);

        DB::transaction(function () use ($user, $newPassword, $policyConfig, $ip): void {
            $oldHash = (string) $user->password;
            $this->history->recordPrevious($user, $oldHash, $policyConfig->historyCount);

            $token = $user->currentAccessToken();
            $currentTokenId = $token instanceof PersonalAccessToken && $token->exists
                ? (int) $token->getKey()
                : null;

            $user->forceFill([
                'password' => $newPassword,
                'debe_cambiar_password' => false,
                'password_actualizada_en' => now(),
                'version' => $user->version + 1,
            ])->save();

            $tokens = $user->tokens();

            if ($currentTokenId !== null) {
                $tokens->where('id', '!=', $currentTokenId);
            }

            $revoked = $tokens->delete();

            event(new UserPasswordChanged(
                userId: $user->id,
                changedBy: $user->id,
                revokedSessions: $revoked,
                ip: $ip,
            ));
        });
    }
}
