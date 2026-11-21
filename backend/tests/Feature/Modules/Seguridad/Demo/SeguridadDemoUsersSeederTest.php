<?php

namespace Tests\Feature\Modules\Seguridad\Demo;

use App\Models\User;
use Database\Seeders\SeguridadDemoUsersSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class SeguridadDemoUsersSeederTest extends SeguridadTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'demo.password' => 'Password12',
            'demo.force_password_change' => false,
        ]);
    }

    public function test_creates_the_three_demo_users_with_roles_and_branch(): void
    {
        $this->seed(SeguridadDemoUsersSeeder::class);

        $expected = [
            'superadmin.demo@talitacumi.test' => 'superadmin',
            'admin.demo@talitacumi.test' => 'administracion',
            'recepcion.demo@talitacumi.test' => 'recepcion',
        ];

        foreach ($expected as $email => $role) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->activo);
            $this->assertFalse($user->debe_cambiar_password);
            $this->assertTrue(Hash::check('Password12', (string) $user->password));
            $this->assertTrue($user->hasRole($role));
            $this->assertNotEmpty($user->branches);
        }

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('sucursal', 1);
    }

    public function test_is_idempotent_and_does_not_reset_existing_passwords(): void
    {
        $this->seed(SeguridadDemoUsersSeeder::class);

        $user = User::query()->where('email', 'superadmin.demo@talitacumi.test')->firstOrFail();
        $originalHash = (string) $user->password;

        config(['demo.password' => 'OtraClave123!']);

        $this->seed(SeguridadDemoUsersSeeder::class);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('sucursal', 1);
        $this->assertSame($originalHash, (string) $user->fresh()->password);
        $this->assertTrue(Hash::check('Password12', (string) $user->fresh()->password));
    }

    public function test_rejects_a_password_that_does_not_meet_the_policy(): void
    {
        config(['demo.password' => 'password']);

        try {
            $this->seed(SeguridadDemoUsersSeeder::class);
            $this->fail('El seeder debía rechazar la contraseña.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no cumple la política', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('sucursal', 0);
    }

    public function test_can_force_password_change_for_demo_users(): void
    {
        config(['demo.force_password_change' => true]);

        $this->seed(SeguridadDemoUsersSeeder::class);

        $user = User::query()->where('email', 'superadmin.demo@talitacumi.test')->firstOrFail();
        $this->assertTrue($user->debe_cambiar_password);

        $response = $this->login('superadmin.demo', 'Password12')->assertOk();
        $token = (string) $response->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/seguridad/usuarios')
            ->assertForbidden()
            ->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');
    }

    public function test_all_demo_roles_can_login_and_receive_their_permissions(): void
    {
        $this->seed(SeguridadDemoUsersSeeder::class);

        $tokens = [];

        foreach ([
            'superadmin.demo' => 'superadmin',
            'admin.demo' => 'administracion',
            'recepcion.demo' => 'recepcion',
        ] as $identifier => $role) {
            $response = $this->postJson('/api/auth/login', [
                'identificador' => $identifier,
                'password' => 'Password12',
            ])->assertOk();

            $this->assertSame($role, $response->json('data.user.rol'));
            $tokens[$role] = (string) $response->json('data.token');
        }

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokens['superadmin'])
            ->getJson('/api/seguridad/usuarios')
            ->assertOk();

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokens['administracion'])
            ->getJson('/api/seguridad/usuarios')
            ->assertOk();

        $recepcion = User::query()->where('email', 'recepcion.demo@talitacumi.test')->firstOrFail();
        $this->assertFalse($recepcion->hasPermissionTo('usuario.ver'));

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokens['recepcion'])
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.rol', 'recepcion');

        app('auth')->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$tokens['recepcion'])
            ->getJson('/api/seguridad/usuarios')
            ->assertForbidden();
    }

    public function test_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        try {
            app(SeguridadDemoUsersSeeder::class)->run();
            $this->fail('El seeder debía bloquearse en producción.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('producción', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }
}
