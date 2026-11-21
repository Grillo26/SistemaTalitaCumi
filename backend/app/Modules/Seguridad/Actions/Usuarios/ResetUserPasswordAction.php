<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Events\UserPasswordReset;
use App\Modules\Seguridad\Exceptions\PasswordPolicyViolationException;
use App\Modules\Seguridad\Exceptions\UserNotFoundException;
use App\Modules\Seguridad\Support\PasswordHistory;
use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\SecurityParameters;
use App\Modules\Seguridad\Support\TemporaryPasswordGenerator;
use App\Modules\Seguridad\Support\UserAccessScope;
use App\Modules\Seguridad\Support\UserSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Reseteo de contraseña con temporal mostrada una vez (02/RF-U10, 02/RF-A04).
 *
 * Invalida todas las sesiones y fuerza el cambio en el siguiente login.
 */
final class ResetUserPasswordAction
{
    public function __construct(
        private readonly UserAccessScope $scope,
        private readonly SecurityParameters $parameters,
        private readonly PasswordHistory $history,
        private readonly TemporaryPasswordGenerator $generator,
    ) {}

    /**
     * @return array{user: User, password_temporal: string, sessions_invalidated: int}
     */
    public function __invoke(User $target, User $actor, ?string $ip): array
    {
        $this->scope->assertCanAccess($actor, $target);

        $policy = $this->parameters->passwordPolicy();
        $temporaryPassword = $this->generator->generate(max(16, $policy->minLength));
        $errors = (new PasswordPolicy($policy))->validate($temporaryPassword);

        if ($errors !== []) {
            throw new PasswordPolicyViolationException($errors);
        }

        return DB::transaction(function () use ($target, $actor, $ip, $policy, $temporaryPassword): array {
            $user = User::query()
                ->whereKey($target->id)
                ->lockForUpdate()
                ->first();

            if ($user === null) {
                throw new UserNotFoundException($target->id);
            }

            $before = UserSnapshot::from($user);

            $this->history->recordPrevious($user, (string) $user->password, $policy->historyCount);

            $user->forceFill([
                'password' => $temporaryPassword,
                'debe_cambiar_password' => true,
                'password_actualizada_en' => now(),
                'version' => $user->version + 1,
            ])->save();

            $sessionsInvalidated = $user->tokens()->delete();

            $user->refresh();

            event(new UserPasswordReset(
                userId: $user->id,
                before: $before,
                after: UserSnapshot::from($user),
                sessionsInvalidated: $sessionsInvalidated,
                actorId: $actor->id,
                ip: $ip,
            ));

            return [
                'user' => $user,
                'password_temporal' => $temporaryPassword,
                'sessions_invalidated' => $sessionsInvalidated,
            ];
        });
    }
}
