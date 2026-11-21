<?php

namespace App\Modules\Seguridad\Actions\Autenticacion;

use App\Models\User;
use App\Modules\Seguridad\Enums\AuditResult;
use App\Modules\Seguridad\Events\UserLocked;
use App\Modules\Seguridad\Events\UserLoggedIn;
use App\Modules\Seguridad\Events\UserLoginFailed;
use App\Modules\Seguridad\Exceptions\InvalidCredentialsException;
use App\Modules\Seguridad\Support\PasswordHistory;
use App\Modules\Seguridad\Support\SecurityParameters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Inicio de sesión con token (02/RF-A01, 02/RF-A05, 02/RF-A07, 02/RF-A11,
 * 02/RF-A15, 02/RF-A16).
 *
 * Mensajes siempre genéricos. El bloqueo es temporal y se reinicia al
 * expirar. La emisión del token y la bitácora viven en la misma transacción.
 */
final class LoginAction
{
    public function __construct(
        private readonly SecurityParameters $parameters,
        private readonly PasswordHistory $history,
    ) {}

    /**
     * @return array{user: User, token: string, expires_at: string}
     */
    public function __invoke(
        string $identifier,
        string $password,
        ?string $ip,
        ?string $userAgent,
    ): array {
        $identifier = trim($identifier);
        $normalized = mb_strtolower($identifier);

        $user = User::query()
            ->whereRaw('lower(email) = ?', [$normalized])
            ->orWhereRaw('lower(username) = ?', [$normalized])
            ->first();

        if ($user === null) {
            $this->burnTime($password);
            $this->registerFailure(null, $identifier, 'credenciales_invalidas', AuditResult::FALLO, $ip, $userAgent);

            throw new InvalidCredentialsException;
        }

        if ($user->estaBloqueado()) {
            $this->burnTime($password, (string) $user->password);
            $this->registerFailure($user->id, $identifier, 'cuenta_bloqueada', AuditResult::DENEGADO, $ip, $userAgent);

            throw new InvalidCredentialsException;
        }

        if ($user->bloqueado_hasta !== null) {
            $user->forceFill([
                'bloqueado_hasta' => null,
                'intentos_fallidos' => 0,
            ])->save();
        }

        if (! Hash::check($password, (string) $user->password)) {
            $this->registerFailedAttempt($user, $identifier, $ip, $userAgent);

            throw new InvalidCredentialsException;
        }

        if (! $user->activo) {
            $this->registerFailure($user->id, $identifier, 'cuenta_inactiva', AuditResult::DENEGADO, $ip, $userAgent);

            throw new InvalidCredentialsException;
        }

        return DB::transaction(function () use ($user, $ip, $userAgent): array {
            $previousLastAccess = $user->ultimo_acceso?->toIso8601String();

            $this->applyPasswordExpiry($user);

            $user->forceFill([
                'ultimo_acceso' => now(),
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ])->save();

            $replacedSessions = $this->pruneSessions($user, $this->parameters->maxSessions());

            $expiresAt = now()->addMinutes($this->parameters->sessionAbsoluteMinutes());
            $newToken = $user->createToken('sesion', ['*'], $expiresAt);
            $tokenId = $newToken->accessToken->getKey();

            event(new UserLoggedIn(
                userId: $user->id,
                tokenId: $tokenId,
                expiresAt: $expiresAt->toIso8601String(),
                previousLastAccess: $previousLastAccess,
                replacedSessions: $replacedSessions,
                ip: $ip,
                userAgent: $userAgent,
            ));

            return [
                'user' => $user->refresh(),
                'token' => $newToken->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
            ];
        });
    }

    private function registerFailedAttempt(
        User $user,
        string $identifier,
        ?string $ip,
        ?string $userAgent,
    ): void {
        DB::transaction(function () use ($user, $identifier, $ip, $userAgent): void {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $attempts = $locked->intentos_fallidos + 1;
            $maxAttempts = $this->parameters->loginMaxAttempts();
            $blockedUntil = null;

            if ($attempts >= $maxAttempts) {
                $blockedUntil = now()->addMinutes($this->parameters->loginLockMinutes());
            }

            $locked->forceFill([
                'intentos_fallidos' => $attempts,
                'bloqueado_hasta' => $blockedUntil,
            ])->save();

            event(new UserLoginFailed(
                userId: $locked->id,
                identifier: $identifier,
                reason: 'credenciales_invalidas',
                result: AuditResult::FALLO,
                ip: $ip,
                userAgent: $userAgent,
            ));

            if ($blockedUntil !== null) {
                event(new UserLocked(
                    userId: $locked->id,
                    failedAttempts: $attempts,
                    blockedUntil: $blockedUntil->toIso8601String(),
                    ip: $ip,
                ));
            }
        });
    }

    private function registerFailure(
        ?int $userId,
        string $identifier,
        string $reason,
        AuditResult $result,
        ?string $ip,
        ?string $userAgent,
    ): void {
        event(new UserLoginFailed(
            userId: $userId,
            identifier: $identifier,
            reason: $reason,
            result: $result,
            ip: $ip,
            userAgent: $userAgent,
        ));
    }

    private function applyPasswordExpiry(User $user): void
    {
        $expiryDays = $this->parameters->passwordPolicy()->expiryDays;

        if ($expiryDays <= 0) {
            return;
        }

        $updatedAt = $user->password_actualizada_en;

        if ($updatedAt === null || $updatedAt->lt(now()->subDays($expiryDays))) {
            $user->forceFill(['debe_cambiar_password' => true])->save();
        }
    }

    /**
     * @return list<int>
     */
    private function pruneSessions(User $user, int $maxSessions): array
    {
        $user->tokens()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $count = $user->tokens()->count();
        $replaced = [];

        while ($count >= $maxSessions) {
            $oldest = $user->tokens()->orderBy('created_at')->orderBy('id')->first();

            if ($oldest === null) {
                break;
            }

            $replaced[] = (int) $oldest->getKey();
            $oldest->delete();
            $count--;
        }

        return $replaced;
    }

    private function burnTime(string $password, ?string $hash = null): void
    {
        Hash::check($password, $hash ?? self::dummyHash());
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('dummy-password-for-timing');
    }
}
