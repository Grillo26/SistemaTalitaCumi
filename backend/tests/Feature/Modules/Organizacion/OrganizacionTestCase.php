<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class OrganizacionTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
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
}
