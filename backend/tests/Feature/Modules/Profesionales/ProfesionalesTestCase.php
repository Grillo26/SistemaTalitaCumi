<?php

namespace Tests\Feature\Modules\Profesionales;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FakeUserBranchAccessContract;
use Tests\TestCase;

abstract class ProfesionalesTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function actingAsRole(string $role): User
    {
        $user = $this->userWithRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsSuperadmin(): User
    {
        return $this->actingAsRole('superadmin');
    }

    protected function branch(array $overrides = []): Branch
    {
        return Branch::factory()->create($overrides);
    }

    protected function especialidad(string $name = 'Psicología'): Especialidad
    {
        $especialidad = Especialidad::query()
            ->whereRaw('lower(trim(nombre)) = ?', [mb_strtolower($name)])
            ->first();

        return $especialidad ?? Especialidad::factory()->create(['nombre' => $name]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function profesional(array $overrides = [], ?Especialidad $principal = null): Profesional
    {
        $principal ??= $this->especialidad();

        $profesional = Profesional::factory()->create($overrides);

        ProfesionalEspecialidad::query()->create([
            'profesional_id' => $profesional->id,
            'especialidad_id' => $principal->id,
            'tipo' => TipoEspecialidad::PRINCIPAL,
            'activo' => true,
        ]);

        return $profesional->refresh();
    }

    protected function assignBranch(Profesional $profesional, Branch $branch, bool $active = true): ProfesionalSucursal
    {
        return ProfesionalSucursal::query()->create([
            'profesional_id' => $profesional->id,
            'sucursal_id' => $branch->id,
            'activo' => $active,
            'motivo' => null,
            'version' => 1,
        ]);
    }

    protected function withBranch(User $user, Branch $branch): void
    {
        $user->branches()->syncWithoutDetaching([$branch->id]);
    }

    /**
     * @param  list<int>  $branchIds
     */
    protected function fakeAccessFor(User $user, array $branchIds): FakeUserBranchAccessContract
    {
        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = $branchIds;
        $this->app->instance(UserBranchAccessContract::class, $access);

        return $access;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function profesionalPayload(array $overrides = []): array
    {
        return array_merge([
            'nombre_completo' => 'Ana Profesional',
            'ci' => '12345678',
            'telefono' => '71234567',
            'telefono_2' => null,
            'email' => 'ana.profesional@example.test',
            'matricula' => null,
            'activo' => true,
            'especialidad_principal_id' => $this->especialidad()->id,
            'especialidades_secundarias' => [],
        ], $overrides);
    }
}
