<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserCrudTest extends SeguridadTestCase
{
    public function test_superadmin_creates_user_with_role_branches_and_temporary_password(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        Sanctum::actingAs($actor);

        $response = $this->postJson('/api/seguridad/usuarios', [
            'name' => 'Nuevo Usuario',
            'documento' => '1234567',
            'email' => 'nuevo@example.test',
            'username' => 'nuevo.usuario',
            'rol_id' => $this->roleId('administracion'),
            'sucursales' => [$branch->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nombre_completo', 'Nuevo Usuario')
            ->assertJsonPath('data.email', 'nuevo@example.test')
            ->assertJsonPath('data.rol.nombre', 'administracion')
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.debe_cambiar_password', true)
            ->assertJsonPath('data.sucursales.0.id', $branch->id)
            ->assertJsonPath('data.can.update', true)
            ->assertJsonStructure(['password_temporal']);

        $temporaryPassword = (string) $response->json('password_temporal');

        $this->assertNotSame('', $temporaryPassword);

        $user = User::query()->where('email', 'nuevo@example.test')->firstOrFail();

        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertTrue($user->hasRole('administracion'));
        $this->assertTrue($user->debe_cambiar_password);
        $this->assertDatabaseHas('usuario_sucursal', [
            'usuario_id' => $user->id,
            'sucursal_id' => $branch->id,
        ]);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $user->id)
            ->where('accion', 'CREAR')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('exito', $entry->resultado);
        $this->assertStringNotContainsString($temporaryPassword, (string) json_encode($entry->datos_nuevos));

        $this->login('nuevo@example.test', $temporaryPassword)
            ->assertOk()
            ->assertJsonPath('data.user.debe_cambiar_password', true);
    }

    public function test_duplicate_email_username_and_documento_are_rejected(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        $this->managedUser('administracion', $branch, [
            'email' => 'duplicado@example.test',
            'username' => 'duplicado',
            'documento' => '9999999',
        ]);

        Sanctum::actingAs($actor);

        $base = [
            'name' => 'Otro Usuario',
            'documento' => '1111111',
            'email' => 'otro@example.test',
            'username' => 'otro.usuario',
            'rol_id' => $this->roleId('recepcion'),
            'sucursales' => [$branch->id],
        ];

        foreach ([
            'email' => 'duplicado@example.test',
            'username' => 'duplicado',
            'documento' => '9999999',
        ] as $field => $value) {
            $this->postJson('/api/seguridad/usuarios', array_merge($base, [$field => $value]))
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }
    }

    public function test_create_requires_role_and_active_branches(): void
    {
        $branch = $this->branch();
        $inactiveBranch = $this->branch(['activo' => false]);
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        Sanctum::actingAs($actor);

        $payload = [
            'name' => 'Sin Rol',
            'documento' => '2222222',
            'email' => 'sinrol@example.test',
            'username' => 'sinrol',
            'sucursales' => [$branch->id],
        ];

        $this->postJson('/api/seguridad/usuarios', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('rol_id');

        $this->postJson('/api/seguridad/usuarios', array_merge($payload, [
            'rol_id' => $this->roleId('recepcion'),
            'sucursales' => [],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('sucursales');

        $this->postJson('/api/seguridad/usuarios', array_merge($payload, [
            'rol_id' => $this->roleId('recepcion'),
            'sucursales' => [$inactiveBranch->id],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'USER_BRANCH_INVALID')
            ->assertJsonValidationErrors('sucursales');
    }

    public function test_update_changes_data_role_and_branches_and_invalidates_sessions(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branchA);
        $token = $target->createToken('target', ['*'], now()->addHour());

        Sanctum::actingAs($actor);

        $response = $this->patchJson("/api/seguridad/usuarios/{$target->id}", [
            'name' => 'Usuario Editado',
            'documento' => '3333333',
            'email' => 'editado@example.test',
            'username' => 'usuario.editado',
            'rol_id' => $this->roleId('recepcion'),
            'sucursales' => [$branchA->id, $branchB->id],
            'version' => 1,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.nombre_completo', 'Usuario Editado')
            ->assertJsonPath('data.rol.nombre', 'recepcion')
            ->assertJsonPath('data.version', 2)
            ->assertJsonCount(2, 'data.sucursales');

        $target->refresh();

        $this->assertTrue($target->hasRole('recepcion'));
        $this->assertSame(2, $target->version);
        $this->assertNull(PersonalAccessToken::findToken($token->plainTextToken));

        $entry = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'EDITAR')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->critico);
        $this->assertTrue((bool) $entry->datos_nuevos['rol_cambiado']);
        $this->assertSame(1, $entry->datos_nuevos['sesiones_invalidadas']);
        $this->assertArrayHasKey('rol', $entry->datos_anteriores);
        $this->assertArrayHasKey('rol', $entry->datos_nuevos);
    }

    public function test_role_change_recalculates_permissions_on_next_request(): void
    {
        $branch = $this->branch();
        $target = $this->managedUser('administracion', $branch);
        $superadmin = $this->superadmin();

        Sanctum::actingAs($target);
        $this->getJson('/api/seguridad/usuarios')->assertOk();

        Sanctum::actingAs($superadmin);

        $this->patchJson("/api/seguridad/usuarios/{$target->id}", [
            'rol_id' => $this->roleId('recepcion'),
            'version' => 1,
        ])->assertOk();

        Sanctum::actingAs($target->fresh());

        $this->getJson('/api/seguridad/usuarios')->assertForbidden();
    }

    public function test_stale_version_returns_conflict(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->patchJson("/api/seguridad/usuarios/{$target->id}", [
            'name' => 'Nombre Nuevo',
            'version' => 99,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'USER_VERSION_CONFLICT');
    }

    public function test_noop_update_does_not_increment_version_or_audit(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch, [
            'documento' => '4444444',
            'username' => 'target.user',
        ]);

        $before = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'EDITAR')
            ->count();

        Sanctum::actingAs($actor);

        $this->patchJson("/api/seguridad/usuarios/{$target->id}", [
            'name' => $target->name,
            'documento' => $target->documento,
            'email' => $target->email,
            'username' => $target->username,
            'rol_id' => $this->roleId('administracion'),
            'sucursales' => [$branch->id],
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.version', 1);

        $this->assertSame($before, BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $target->id)
            ->where('accion', 'EDITAR')
            ->count());
    }

    public function test_delete_is_always_rejected(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $target = $this->managedUser('administracion', $branch);

        Sanctum::actingAs($actor);

        $this->deleteJson("/api/seguridad/usuarios/{$target->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'USER_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_administracion_cannot_create_or_edit_but_can_consult(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);
        $target = $this->managedUser('recepcion', $branch);

        Sanctum::actingAs($admin);

        $this->postJson('/api/seguridad/usuarios', $this->userPayload())->assertForbidden();
        $this->patchJson("/api/seguridad/usuarios/{$target->id}", [
            'name' => 'No Permitido',
            'version' => 1,
        ])->assertForbidden();

        $this->getJson('/api/seguridad/usuarios')->assertOk();
        $this->getJson("/api/seguridad/usuarios/{$target->id}")->assertOk();
        $this->getJson('/api/seguridad/usuarios/opciones')->assertOk();
    }

    public function test_recepcion_has_no_access_to_users(): void
    {
        $branch = $this->branch();
        $reception = $this->managedUser('recepcion', $branch);

        Sanctum::actingAs($reception);

        $this->getJson('/api/seguridad/usuarios')->assertForbidden();
        $this->getJson('/api/seguridad/usuarios/opciones')->assertForbidden();
    }

    public function test_options_returns_role_catalog_with_labels(): void
    {
        $branch = $this->branch(['codigo' => 'SUC-ACTIVA']);
        $inactive = $this->branch(['codigo' => 'SUC-INACTIVA', 'activo' => false]);
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        Sanctum::actingAs($actor);

        $response = $this->getJson('/api/seguridad/usuarios/opciones')
            ->assertOk()
            ->assertJsonCount(3, 'data.roles')
            ->assertJsonCount(1, 'data.sucursales')
            ->assertJsonPath('data.sucursales.0.id', $branch->id);

        $roles = collect($response->json('data.roles'));

        $this->assertSame('Superadmin', $roles->firstWhere('nombre', 'superadmin')['label']);
        $this->assertSame('Administración', $roles->firstWhere('nombre', 'administracion')['label']);
        $this->assertSame('Recepción', $roles->firstWhere('nombre', 'recepcion')['label']);
        $this->assertNotContains($inactive->id, collect($response->json('data.sucursales'))->pluck('id')->all());
    }

    public function test_options_limits_branches_to_the_administration_scope(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);
        $admin = $this->managedUser('administracion', $branchA);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/seguridad/usuarios/opciones')
            ->assertOk()
            ->assertJsonCount(1, 'data.sucursales')
            ->assertJsonPath('data.sucursales.0.id', $branchA->id);

        $this->assertNotContains(
            $branchB->id,
            collect($response->json('data.sucursales'))->pluck('id')->all(),
        );
    }

    public function test_creates_user_without_email_and_can_login_with_username(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();

        Sanctum::actingAs($actor);

        $response = $this->postJson('/api/seguridad/usuarios', [
            'name' => 'Usuario Sin Correo',
            'documento' => '7654322',
            'username' => 'usuario.sin.correo',
            'rol_id' => $this->roleId('recepcion'),
            'sucursales' => [$branch->id],
        ])->assertCreated()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.username', 'usuario.sin.correo');

        $temporaryPassword = (string) $response->json('password_temporal');

        $this->postJson('/api/auth/login', [
            'identificador' => 'usuario.sin.correo',
            'password' => $temporaryPassword,
        ])->assertOk()
            ->assertJsonPath('data.user.username', 'usuario.sin.correo')
            ->assertJsonPath('data.user.email', null);
    }
}
