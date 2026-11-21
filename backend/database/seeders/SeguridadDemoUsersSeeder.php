<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchIdentityNormalizationRule;
use App\Modules\Seguridad\Rules\UserIdentityNormalizationRule;
use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\PasswordPolicyConfig;
use App\Modules\Seguridad\Support\SecurityParameters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Crea los usuarios demo para iniciar sesión con los tres roles.
 *
 * No se ejecuta desde DatabaseSeeder. Es idempotente: si el correo demo ya
 * existe, no se resetea la contraseña ni se modifica el usuario. Se bloquea
 * en producción y usa una contraseña fija solo para desarrollo.
 */
final class SeguridadDemoUsersSeeder extends Seeder
{
    private const DEMO_ROLES = ['superadmin', 'administracion', 'recepcion'];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Los usuarios demo no se pueden crear en producción. Este seeder es solo para desarrollo.',
            );
        }

        $this->assertRolesExist();

        $password = (string) config('demo.password', 'Password12');
        $forceChange = (bool) config('demo.force_password_change', false);
        $policy = $this->passwordPolicy();

        $errors = (new PasswordPolicy($policy))->validate($password);

        if ($errors !== []) {
            throw new RuntimeException(
                'La contraseña demo no cumple la política vigente: '.implode(' ', $errors),
            );
        }

        $branch = $this->resolveBranch();
        $allBranchIds = $this->activeBranchIds();

        foreach ($this->demoUsers() as $key => $data) {
            $this->createUser($key, $data, $password, $forceChange, $branch, $allBranchIds);
        }

        $this->command?->info('Seeder demo finalizado. Los usuarios existentes no se modificaron.');
    }

    private function assertRolesExist(): void
    {
        $count = Role::query()
            ->whereIn('name', self::DEMO_ROLES)
            ->where('guard_name', 'web')
            ->count();

        if ($count !== count(self::DEMO_ROLES)) {
            throw new RuntimeException(
                'Faltan roles base. Ejecutá primero `php artisan db:seed` para crear roles y permisos.',
            );
        }
    }

    private function passwordPolicy(): PasswordPolicyConfig
    {
        try {
            return app(SecurityParameters::class)->passwordPolicy();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'No se pudo leer la política de contraseñas. Ejecutá primero `php artisan db:seed` para cargar la configuración.',
                0,
                $exception,
            );
        }
    }

    private function resolveBranch(): Branch
    {
        $active = Branch::query()
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if ($active !== null) {
            return $active;
        }

        $code = BranchIdentityNormalizationRule::normalizeCode(
            (string) config('demo.branch.codigo', 'DEMO-CENTRAL'),
        );
        $name = BranchIdentityNormalizationRule::normalizeName(
            (string) config('demo.branch.nombre', 'Sucursal Demo'),
        );

        $existing = Branch::query()
            ->whereRaw('lower(trim(codigo)) = ?', [mb_strtolower($code)])
            ->orWhereRaw('lower(trim(nombre)) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing !== null) {
            if (! $existing->activo) {
                $existing->forceFill([
                    'activo' => true,
                    'version' => $existing->version + 1,
                ])->save();

                $this->command?->warn(
                    'Se reactivó la sucursal demo existente para poder asignar usuarios.',
                );
            }

            return $existing;
        }

        return Branch::query()->create([
            'codigo' => $code,
            'nombre' => $name,
            'activo' => true,
            'version' => 1,
        ]);
    }

    /**
     * @return list<int>
     */
    private function activeBranchIds(): array
    {
        return Branch::query()
            ->where('activo', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function demoUsers(): array
    {
        /** @var array<string, array<string, string>> $users */
        $users = config('demo.users', []);

        if ($users === []) {
            throw new RuntimeException('No hay usuarios demo configurados en config/demo.php.');
        }

        return $users;
    }

    /**
     * @param  array<string, string>  $data
     * @param  list<int>  $allBranchIds
     */
    private function createUser(
        string $key,
        array $data,
        string $password,
        bool $forceChange,
        Branch $branch,
        array $allBranchIds,
    ): void {
        $name = UserIdentityNormalizationRule::name($data['name']);
        $email = UserIdentityNormalizationRule::email($data['email']);
        $username = UserIdentityNormalizationRule::username($data['username']);
        $documento = UserIdentityNormalizationRule::documento($data['documento']);
        $roleName = $data['role'];

        $existing = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if ($existing !== null) {
            $this->command?->warn(sprintf(
                'Usuario demo omitido: %s ya existe%s.',
                $email,
                $existing->activo ? '' : ' (inactivo)',
            ));

            return;
        }

        if (User::query()->whereRaw('lower(username) = ?', [$username])->exists()) {
            throw new RuntimeException(
                "El nombre de usuario demo `{$username}` ya está en uso por otro usuario. Cambiá DEMO_*_USERNAME.",
            );
        }

        if (User::query()->whereRaw('upper(documento) = ?', [$documento])->exists()) {
            throw new RuntimeException(
                "El documento demo `{$documento}` ya está en uso por otro usuario. Cambiá DEMO_*_DOCUMENTO.",
            );
        }

        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->first();

        if ($role === null) {
            throw new RuntimeException("No existe el rol `{$roleName}`. Ejecutá `php artisan db:seed`.");
        }

        $branchIds = $roleName === 'superadmin' && $allBranchIds !== []
            ? $allBranchIds
            : [$branch->id];

        DB::transaction(function () use (
            $name,
            $email,
            $username,
            $documento,
            $password,
            $forceChange,
            $role,
            $branchIds,
        ): void {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'documento' => $documento,
                'password' => $password,
                'activo' => true,
                'debe_cambiar_password' => $forceChange,
                'password_actualizada_en' => now(),
                'version' => 1,
            ]);

            $user->syncRoles([$role]);
            $user->branches()->sync($branchIds);
        });

        $this->command?->info(sprintf(
            'Usuario demo creado: %s | %s | rol: %s | contraseña: %s',
            $key,
            $email,
            $roleName,
            $password,
        ));
    }
}
