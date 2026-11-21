<?php

namespace Tests\Feature\Modules\Seguridad\Usuarios;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class UserListTest extends SeguridadTestCase
{
    public function test_administracion_only_sees_users_of_its_branches(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $adminA = $this->managedUser('administracion', $branchA, [
            'name' => 'Admin A',
            'email' => 'admin.a@example.test',
        ]);
        $this->managedUser('administracion', $branchB, [
            'name' => 'Admin B',
            'email' => 'admin.b@example.test',
        ]);
        $this->managedUser('recepcion', $branchA, [
            'name' => 'Recepción A',
            'email' => 'reception.a@example.test',
        ]);
        $this->managedUser('recepcion', null, [
            'name' => 'Sin Sucursal',
            'email' => 'sin.sucursal@example.test',
        ]);

        Sanctum::actingAs($adminA);

        $response = $this->getJson('/api/seguridad/usuarios')->assertOk();
        $emails = collect($response->json('data'))->pluck('email')->all();

        $this->assertContains('admin.a@example.test', $emails);
        $this->assertContains('reception.a@example.test', $emails);
        $this->assertNotContains('admin.b@example.test', $emails);
        $this->assertNotContains('sin.sucursal@example.test', $emails);
        $this->assertSame(2, $response->json('meta.total'));
        $this->assertFalse($response->json('can.create'));
        $this->assertFalse($response->json('can.export'));

        $row = collect($response->json('data'))->firstWhere('email', 'reception.a@example.test');

        $this->assertFalse($row['can']['update']);
        $this->assertFalse($row['can']['change_status']);
        $this->assertFalse($row['can']['reset_password']);
    }

    public function test_superadmin_sees_all_users(): void
    {
        $branch = $this->branch();
        $actor = $this->superadmin();
        $actor->branches()->attach($branch->id);

        $this->managedUser('administracion', $branch, ['email' => 'visible@example.test']);
        $this->managedUser('recepcion', null, ['email' => 'sin.sucursal@example.test']);

        Sanctum::actingAs($actor);

        $response = $this->getJson('/api/seguridad/usuarios')->assertOk();
        $emails = collect($response->json('data'))->pluck('email')->all();

        $this->assertContains('visible@example.test', $emails);
        $this->assertContains('sin.sucursal@example.test', $emails);
        $this->assertTrue($response->json('can.create'));
    }

    public function test_show_outside_scope_returns_forbidden(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $adminA = $this->managedUser('administracion', $branchA);
        $targetB = $this->managedUser('recepcion', $branchB);

        Sanctum::actingAs($adminA);

        $this->getJson("/api/seguridad/usuarios/{$targetB->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'USER_ACCESS_DENIED');
    }

    public function test_filters_by_role_branch_and_state(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $actor = $this->superadmin();
        $actor->branches()->attach($branchA->id);

        $adminA = $this->managedUser('administracion', $branchA, [
            'name' => 'Admin Filtro',
            'email' => 'admin.filtro@example.test',
        ]);
        $receptionA = $this->managedUser('recepcion', $branchA, [
            'name' => 'Recepción Inactiva',
            'email' => 'reception.inactiva@example.test',
            'activo' => false,
        ]);
        $this->managedUser('recepcion', $branchB, [
            'name' => 'Recepción Bloqueada',
            'email' => 'reception.bloqueada@example.test',
            'bloqueado_hasta' => now()->addMinutes(10),
            'intentos_fallidos' => 5,
        ]);

        Sanctum::actingAs($actor);

        $byRole = $this->getJson('/api/seguridad/usuarios?rol_id='.$this->roleId('recepcion'))->assertOk();
        $roleEmails = collect($byRole->json('data'))->pluck('email')->all();
        $this->assertContains('reception.inactiva@example.test', $roleEmails);
        $this->assertContains('reception.bloqueada@example.test', $roleEmails);
        $this->assertNotContains('admin.filtro@example.test', $roleEmails);

        $byBranch = $this->getJson('/api/seguridad/usuarios?sucursal_id='.$branchA->id)->assertOk();
        $branchEmails = collect($byBranch->json('data'))->pluck('email')->all();
        $this->assertContains('admin.filtro@example.test', $branchEmails);
        $this->assertContains('reception.inactiva@example.test', $branchEmails);
        $this->assertNotContains('reception.bloqueada@example.test', $branchEmails);

        $inactive = $this->getJson('/api/seguridad/usuarios?estado=inactivo')->assertOk();
        $inactiveEmails = collect($inactive->json('data'))->pluck('email')->all();
        $this->assertContains('reception.inactiva@example.test', $inactiveEmails);
        $this->assertNotContains('admin.filtro@example.test', $inactiveEmails);

        $blocked = $this->getJson('/api/seguridad/usuarios?estado=bloqueado')->assertOk();
        $blockedEmails = collect($blocked->json('data'))->pluck('email')->all();
        $this->assertContains('reception.bloqueada@example.test', $blockedEmails);
        $this->assertNotContains('admin.filtro@example.test', $blockedEmails);

        $search = $this->getJson('/api/seguridad/usuarios?search=Inactiva')->assertOk();
        $searchEmails = collect($search->json('data'))->pluck('email')->all();
        $this->assertContains('reception.inactiva@example.test', $searchEmails);

        $paged = $this->getJson('/api/seguridad/usuarios?per_page=1&page=1')->assertOk();
        $this->assertCount(1, $paged->json('data'));
        $this->assertSame(1, $paged->json('meta.per_page'));
    }

    public function test_branch_change_recalculates_scope_on_next_request(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $adminA = $this->managedUser('administracion', $branchA);
        $target = $this->managedUser('recepcion', $branchA, [
            'email' => 'cambia.sucursal@example.test',
        ]);
        $superadmin = $this->superadmin();

        Sanctum::actingAs($adminA);
        $this->getJson('/api/seguridad/usuarios')
            ->assertOk()
            ->assertJsonFragment(['email' => 'cambia.sucursal@example.test']);

        Sanctum::actingAs($superadmin);

        $this->patchJson("/api/seguridad/usuarios/{$target->id}", [
            'sucursales' => [$branchB->id],
            'version' => 1,
        ])->assertOk();

        Sanctum::actingAs($adminA->fresh());

        $response = $this->getJson('/api/seguridad/usuarios')->assertOk();

        $this->assertNotContains(
            'cambia.sucursal@example.test',
            collect($response->json('data'))->pluck('email')->all(),
        );

        $this->getJson("/api/seguridad/usuarios/{$target->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'USER_ACCESS_DENIED');
    }

    public function test_administracion_cannot_see_users_without_branches(): void
    {
        $branch = $this->branch();
        $admin = $this->managedUser('administracion', $branch);
        $orphan = $this->managedUser('recepcion', null);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/seguridad/usuarios')->assertOk();
        $emails = collect($response->json('data'))->pluck('email')->all();

        $this->assertNotContains($orphan->email, $emails);
    }
}
