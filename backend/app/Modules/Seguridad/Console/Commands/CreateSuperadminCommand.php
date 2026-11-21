<?php

namespace App\Modules\Seguridad\Console\Commands;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Seguridad\Rules\UserIdentityNormalizationRule;
use App\Modules\Seguridad\Support\PasswordPolicy;
use App\Modules\Seguridad\Support\SecurityParameters;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Bootstrap seguro del primer Superadmin (02/RF-R01 / 02/RN-16).
 *
 * Nunca usa una contraseña por defecto del repositorio. Lee opciones,
 * variables de entorno o prompt interactivo. No imprime la contraseña.
 */
final class CreateSuperadminCommand extends Command
{
    protected $signature = 'seguridad:crear-superadmin
        {--name= : Nombre completo}
        {--email= : Correo electrónico (opcional)}
        {--username= : Nombre de usuario}
        {--documento= : Documento de identidad}
        {--password= : Contraseña inicial (preferir prompt o SUPERADMIN_PASSWORD)}
        {--promote= : ID de un usuario existente a promover a Superadmin}
        {--branch=* : IDs de sucursales a asignar (por defecto todas las activas)}';

    protected $description = 'Crea o promueve el primer Superadmin sin contraseñas por defecto.';

    public function handle(SecurityParameters $security): int
    {
        $role = Role::query()->where('es_superadmin', true)->first()
            ?? Role::query()->where('name', 'superadmin')->where('guard_name', 'web')->first();

        if ($role === null) {
            $this->error('No existe el rol Superadmin. Ejecutá primero `php artisan db:seed`.');

            return self::FAILURE;
        }

        $branches = $this->resolveBranches();

        if ($branches->isEmpty()) {
            $this->warn('No hay sucursales activas. El Superadmin se creará sin sucursales; creá la primera desde Organización → Sucursales.');
        }

        $promoteId = $this->option('promote');

        if ($promoteId !== null && $promoteId !== '') {
            return $this->promote((int) $promoteId, $role, $branches->pluck('id')->all());
        }

        $name = $this->value('name', 'SUPERADMIN_NAME', 'Nombre completo');
        $email = UserIdentityNormalizationRule::emailOrNull(
            $this->value('email', 'SUPERADMIN_EMAIL', 'Correo electrónico (opcional)'),
        );
        $username = mb_strtolower($this->value('username', 'SUPERADMIN_USERNAME', 'Nombre de usuario'));
        $documento = strtoupper($this->value('documento', 'SUPERADMIN_DOCUMENTO', 'Documento de identidad'));
        $password = $this->password();

        if ($name === '' || $username === '' || $documento === '' || $password === '') {
            $this->error('Faltan datos obligatorios. Usá las opciones, las variables SUPERADMIN_* o el modo interactivo.');

            return self::FAILURE;
        }

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('El correo no tiene un formato válido.');

            return self::FAILURE;
        }

        if (preg_match('/^[a-z0-9._-]{3,100}$/', $username) !== 1) {
            $this->error('El nombre de usuario debe tener 3–100 caracteres: letras minúsculas, números, punto, guion o guion bajo.');

            return self::FAILURE;
        }

        if ($email !== null && User::query()->where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con ese correo.');

            return self::FAILURE;
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->error('Ya existe un usuario con ese nombre de usuario.');

            return self::FAILURE;
        }

        if (User::query()->where('documento', $documento)->exists()) {
            $this->error('Ya existe un usuario con ese documento.');

            return self::FAILURE;
        }

        $policy = $security->passwordPolicy();
        $errors = (new PasswordPolicy($policy))->validate($password);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $username, $documento, $password, $role, $branches): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'documento' => $documento,
                'password' => $password,
                'activo' => true,
                'debe_cambiar_password' => true,
                'password_actualizada_en' => now(),
                'version' => 1,
            ]);

            $user->assignRole($role);
            $user->branches()->sync($branches->pluck('id')->all());

            return $user;
        });

        $this->info(sprintf(
            'Superadmin creado: %s (%s). Debe cambiar la contraseña en el primer login.',
            $user->name,
            $user->email ?? 'sin correo',
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $branchIds
     */
    private function promote(int $userId, Role $role, array $branchIds): int
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            $this->error('No existe el usuario indicado.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $role, $branchIds): void {
            $user->forceFill([
                'activo' => true,
                'motivo_estado' => null,
                'estado_cambiado_en' => now(),
                'debe_cambiar_password' => true,
                'version' => $user->version + 1,
            ])->save();

            $user->syncRoles([$role]);
            $user->branches()->sync($branchIds);
        });

        $this->info(sprintf('Usuario promovido a Superadmin: %s (%s).', $user->name, $user->email ?? 'sin correo'));

        return self::SUCCESS;
    }

    private function resolveBranches(): Collection
    {
        /** @var list<string> $ids */
        $ids = $this->option('branch');

        if ($ids !== []) {
            return Branch::query()
                ->whereIn('id', array_map('intval', $ids))
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
        }

        return Branch::query()->where('activo', true)->orderBy('nombre')->get();
    }

    private function value(string $option, string $env, string $question): string
    {
        $value = $this->option($option);

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $envValue = env($env);

        if (is_string($envValue) && trim($envValue) !== '') {
            return trim($envValue);
        }

        if ($this->input->isInteractive()) {
            return trim((string) $this->ask($question));
        }

        return '';
    }

    private function password(): string
    {
        $value = $this->option('password');

        if (is_string($value) && $value !== '') {
            return $value;
        }

        $envValue = env('SUPERADMIN_PASSWORD');

        if (is_string($envValue) && $envValue !== '') {
            return $envValue;
        }

        if (! $this->input->isInteractive()) {
            return '';
        }

        $password = (string) $this->secret('Contraseña inicial');
        $confirmation = (string) $this->secret('Repetí la contraseña inicial');

        if ($password !== $confirmation) {
            $this->error('Las contraseñas no coinciden.');

            return '';
        }

        return $password;
    }
}
