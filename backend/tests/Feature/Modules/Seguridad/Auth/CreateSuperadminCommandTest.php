<?php

namespace Tests\Feature\Modules\Seguridad\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class CreateSuperadminCommandTest extends SeguridadTestCase
{
    public function test_command_creates_superadmin_without_default_password(): void
    {
        $branch = $this->branch(['codigo' => 'SUC-001']);

        $this->artisan('seguridad:crear-superadmin', [
            '--name' => 'Administradora General',
            '--email' => 'admin@talitacumi.bo',
            '--username' => 'admin',
            '--documento' => '1234567',
            '--password' => 'SuperClave123!',
        ])->assertExitCode(0);

        $user = User::query()->where('email', 'admin@talitacumi.bo')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertTrue($user->activo);
        $this->assertTrue($user->debe_cambiar_password);
        $this->assertTrue(Hash::check('SuperClave123!', $user->password));
        $this->assertDatabaseHas('usuario_sucursal', [
            'usuario_id' => $user->id,
            'sucursal_id' => $branch->id,
        ]);
    }

    public function test_command_creates_superadmin_without_branches_when_none_exist(): void
    {
        $this->artisan('seguridad:crear-superadmin', [
            '--name' => 'Administradora General',
            '--email' => 'admin@talitacumi.bo',
            '--username' => 'admin',
            '--documento' => '1234567',
            '--password' => 'SuperClave123!',
        ])
            ->expectsOutputToContain('No hay sucursales activas')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'admin@talitacumi.bo')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertDatabaseCount('usuario_sucursal', 0);
    }

    public function test_command_rejects_duplicate_email(): void
    {
        $this->branch();
        User::factory()->create(['email' => 'admin@talitacumi.bo']);

        $this->artisan('seguridad:crear-superadmin', [
            '--name' => 'Administradora General',
            '--email' => 'admin@talitacumi.bo',
            '--username' => 'admin',
            '--documento' => '1234567',
            '--password' => 'SuperClave123!',
        ])->assertExitCode(1);
    }

    public function test_command_promotes_existing_user(): void
    {
        $branch = $this->branch();
        $user = User::factory()->create([
            'email' => 'legacy@talitacumi.bo',
            'activo' => false,
        ]);

        $this->artisan('seguridad:crear-superadmin', [
            '--promote' => (string) $user->id,
        ])->assertExitCode(0);

        $user->refresh();

        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertTrue($user->activo);
        $this->assertTrue($user->debe_cambiar_password);
        $this->assertDatabaseHas('usuario_sucursal', [
            'usuario_id' => $user->id,
            'sucursal_id' => $branch->id,
        ]);
    }
}
