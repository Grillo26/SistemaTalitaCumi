<?php

namespace Tests\Feature\Modules\Seguridad;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class SeguridadTestCase extends TestCase
{
    use RefreshDatabase;

    protected const PASSWORD = 'Secreta123!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function userWithRole(string $role, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => self::PASSWORD,
        ], $overrides));

        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function superadmin(array $overrides = []): User
    {
        return $this->userWithRole('superadmin', $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function administracion(array $overrides = []): User
    {
        return $this->userWithRole('administracion', $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function recepcion(array $overrides = []): User
    {
        return $this->userWithRole('recepcion', $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function branch(array $overrides = []): Branch
    {
        return Branch::factory()->create($overrides);
    }

    protected function login(string $identifier, string $password = self::PASSWORD): TestResponse
    {
        return $this->postJson('/api/auth/login', [
            'identificador' => $identifier,
            'password' => $password,
        ]);
    }

    protected function actingAsWithToken(User $user): string
    {
        $token = $user->createToken('test', ['*'], now()->addHour());

        return $token->plainTextToken;
    }

    protected function roleId(string $role): int
    {
        return Role::findByName($role, 'web')->id;
    }

    protected function permissionId(string $permission): int
    {
        return Permission::findByName($permission, 'web')->id;
    }

    /**
     * @return list<int>
     */
    protected function rolePermissionIds(string $role): array
    {
        return Role::findByName($role, 'web')
            ->permissions()
            ->pluck('permissions.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function roleVersion(string $role): int
    {
        return (int) Role::findByName($role, 'web')->version;
    }

    protected function withBranch(User $user, ?Branch $branch = null): Branch
    {
        $branch ??= $this->branch();

        $user->branches()->syncWithoutDetaching([$branch->id]);

        return $branch;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function managedUser(
        string $role = 'administracion',
        ?Branch $branch = null,
        array $overrides = [],
    ): User {
        $user = $this->userWithRole($role, $overrides);

        if ($branch !== null) {
            $user->branches()->attach($branch->id);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function userPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuario de Prueba',
            'documento' => '7654321',
            'email' => 'usuario.prueba@example.test',
            'username' => 'usuario.prueba',
            'rol_id' => $this->roleId('administracion'),
            'sucursales' => [$this->branch()->id],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function auditRow(array $attributes, ?CarbonInterface $createdAt = null): BitacoraEntry
    {
        $entry = new BitacoraEntry($attributes);

        if ($createdAt !== null) {
            $entry->created_at = $createdAt;
        }

        $entry->save();

        return $entry;
    }
}
