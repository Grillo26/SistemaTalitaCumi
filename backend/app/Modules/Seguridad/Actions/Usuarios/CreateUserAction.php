<?php

namespace App\Modules\Seguridad\Actions\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Events\UserCreated;
use App\Modules\Seguridad\Exceptions\PasswordPolicyViolationException;
use App\Modules\Seguridad\Exceptions\UserRoleRequiredException;
use App\Modules\Seguridad\Rules\UserIdentityNormalizationRule;
use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\SecurityParameters;
use App\Modules\Seguridad\Support\TemporaryPasswordGenerator;
use App\Modules\Seguridad\Support\UserAccessScope;
use App\Modules\Seguridad\Support\UserSnapshot;
use App\Modules\Seguridad\Support\UserUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Alta de usuario con rol, sucursales y contraseña temporal (02/RF-U01–U05, U12).
 *
 * La contraseña temporal se devuelve una sola vez y nunca se audita.
 */
final class CreateUserAction
{
    public function __construct(
        private readonly UserUniquenessGuard $uniqueness,
        private readonly UserAccessScope $scope,
        private readonly SecurityParameters $parameters,
        private readonly TemporaryPasswordGenerator $generator,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{user: User, password_temporal: string}
     */
    public function __invoke(array $attributes, User $actor, ?string $ip): array
    {
        $name = UserIdentityNormalizationRule::name((string) $attributes['name']);
        $email = UserIdentityNormalizationRule::emailOrNull($attributes['email'] ?? null);
        $username = UserIdentityNormalizationRule::username((string) $attributes['username']);
        $documento = UserIdentityNormalizationRule::documento((string) $attributes['documento']);
        $roleId = (int) $attributes['rol_id'];
        $branchIds = array_values(array_unique(array_map('intval', $attributes['sucursales'])));

        $this->scope->assertCanAssignBranches($actor, $branchIds);

        $role = Role::query()
            ->whereKey($roleId)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            throw new UserRoleRequiredException;
        }

        if ($email !== null) {
            $this->uniqueness->assertAvailable('email', $email);
        }

        $this->uniqueness->assertAvailable('username', $username);
        $this->uniqueness->assertAvailable('documento', $documento);

        $policy = $this->parameters->passwordPolicy();
        $temporaryPassword = $this->generator->generate(max(16, $policy->minLength));
        $errors = (new PasswordPolicy($policy))->validate($temporaryPassword);

        if ($errors !== []) {
            throw new PasswordPolicyViolationException($errors);
        }

        return DB::transaction(function () use (
            $name,
            $email,
            $username,
            $documento,
            $role,
            $branchIds,
            $temporaryPassword,
            $actor,
            $ip,
        ): array {
            try {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'documento' => $documento,
                    'password' => $temporaryPassword,
                    'activo' => true,
                    'debe_cambiar_password' => true,
                    'password_actualizada_en' => now(),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $user->syncRoles([$role]);
            $user->branches()->sync($branchIds);
            $user->unsetRelation('roles')->unsetRelation('permissions');

            event(new UserCreated(
                userId: $user->id,
                snapshot: UserSnapshot::from($user->refresh()),
                actorId: $actor->id,
                ip: $ip,
            ));

            return [
                'user' => $user->refresh(),
                'password_temporal' => $temporaryPassword,
            ];
        });
    }
}
