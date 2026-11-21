<?php

namespace Tests\Feature\Modules\Pacientes;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FakeUserBranchAccessContract;
use Tests\TestCase;

abstract class PacientesTestCase extends TestCase
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

    protected function tutor(array $overrides = []): Tutor
    {
        return Tutor::factory()->create($overrides);
    }

    protected function paciente(array $overrides = []): Paciente
    {
        return Paciente::factory()->create($overrides);
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
    protected function pacientePayload(array $overrides = [], ?Branch $branch = null): array
    {
        $branch ??= $this->branch();

        $payload = [
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'fecha_nacimiento' => '2020-05-10',
            'sexo' => 'F',
            'ci' => (string) fake()->unique()->numerify('########'),
            'tipo_diagnostico_id' => null,
            'sucursal_registro_id' => $branch->id,
            'alergias' => null,
            'condiciones_medicas' => null,
            'observaciones' => null,
            'tutor_principal' => [
                'modo' => 'nuevo',
                'nombre_completo' => 'Ana López',
                'ci' => (string) fake()->unique()->numerify('########'),
                'telefono' => '71234567',
                'telefono_alternativo' => null,
                'email' => null,
                'direccion' => null,
                'parentesco' => 'Madre',
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
