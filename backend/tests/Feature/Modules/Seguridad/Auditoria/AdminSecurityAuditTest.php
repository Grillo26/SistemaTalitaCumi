<?php

namespace Tests\Feature\Modules\Seguridad\Auditoria;

use App\Modules\Seguridad\Actions\Usuarios\CreateUserAction;
use App\Modules\Seguridad\Events\RolePermissionsUpdated;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Support\Collection;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class AdminSecurityAuditTest extends SeguridadTestCase
{
    public function test_security_event_is_replicated_for_each_branch_of_the_affected_user(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $result = app(CreateUserAction::class)(
            $this->userPayload([
                'email' => 'multi.sucursal@example.test',
                'username' => 'multi.sucursal',
                'documento' => '80000001',
                'rol_id' => $this->roleId('recepcion'),
                'sucursales' => [$branchA->id, $branchB->id],
            ]),
            $this->superadmin(),
            '203.0.113.10',
        );

        $rows = BitacoraEntry::query()
            ->where('entidad', 'usuario')
            ->where('entidad_id', $result['user']->id)
            ->where('accion', 'CREAR')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [$branchA->id, $branchB->id],
            $rows->pluck('sucursal_id')->all(),
        );
    }

    public function test_administracion_sees_security_events_of_its_branches_and_not_matrix_events(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);

        $adminA = $this->managedUser('administracion', $branchA);
        $adminB = $this->managedUser('administracion', $branchB);
        $targetA = $this->managedUser('recepcion', $branchA);
        $targetB = $this->managedUser('recepcion', $branchB);

        $superadmin = $this->superadmin();
        $superadmin->branches()->attach($branchA->id);
        $action = app(CreateUserAction::class);

        $createdA = $action(
            $this->userPayload([
                'email' => 'creado.a@example.test',
                'username' => 'creado.a',
                'documento' => '80000002',
                'rol_id' => $this->roleId('recepcion'),
                'sucursales' => [$branchA->id],
            ]),
            $superadmin,
            '203.0.113.11',
        );

        $createdB = $action(
            $this->userPayload([
                'email' => 'creado.b@example.test',
                'username' => 'creado.b',
                'documento' => '80000003',
                'rol_id' => $this->roleId('recepcion'),
                'sucursales' => [$branchB->id],
            ]),
            $superadmin,
            '203.0.113.12',
        );

        $this->login($targetA->email)->assertOk();
        $this->login($targetB->email)->assertOk();
        $this->login($superadmin->email)->assertOk();

        $this->assertSame(1, BitacoraEntry::query()
            ->where('usuario_id', $superadmin->id)
            ->where('entidad', 'sesion')
            ->where('accion', 'INICIAR_SESION')
            ->whereNull('sucursal_id')
            ->count());

        event(new RolePermissionsUpdated(
            roleId: $this->roleId('administracion'),
            before: ['usuario.ver'],
            after: ['usuario.ver', 'rol.ver'],
            beforeVersion: 1,
            version: 2,
            actorId: $superadmin->id,
            ip: '203.0.113.13',
            motivo: null,
            baseline: false,
        ));

        Sanctum::actingAs($adminA);
        $response = $this->getJson('/api/seguridad/bitacora?per_page=50')->assertOk();
        $entries = collect($response->json('data'));

        $this->assertTrue($this->containsEntry($entries, 'usuario', $createdA['user']->id));
        $this->assertFalse($this->containsEntry($entries, 'usuario', $createdB['user']->id));

        $this->assertTrue($entries->contains(
            static fn (array $entry): bool => $entry['entidad'] === 'sesion'
                && $entry['usuario_id'] === $targetA->id,
        ));
        $this->assertFalse($entries->contains(
            static fn (array $entry): bool => $entry['entidad'] === 'sesion'
                && $entry['usuario_id'] === $targetB->id,
        ));

        $this->assertFalse($entries->contains(
            static fn (array $entry): bool => $entry['entidad'] === 'matriz_permisos',
        ));

        $this->assertFalse($entries->contains(
            static fn (array $entry): bool => $entry['entidad'] === 'sesion'
                && $entry['usuario_id'] === $superadmin->id,
        ));

        Sanctum::actingAs($adminB);
        $other = collect($this->getJson('/api/seguridad/bitacora?per_page=50')->assertOk()->json('data'));
        $this->assertTrue($this->containsEntry($other, 'usuario', $createdB['user']->id));
        $this->assertFalse($this->containsEntry($other, 'usuario', $createdA['user']->id));

        Sanctum::actingAs($superadmin);
        $all = collect($this->getJson('/api/seguridad/bitacora?per_page=50')->assertOk()->json('data'));
        $this->assertTrue($this->containsEntry($all, 'usuario', $createdA['user']->id));
        $this->assertTrue($this->containsEntry($all, 'usuario', $createdB['user']->id));
        $this->assertTrue($all->contains(
            static fn (array $entry): bool => $entry['entidad'] === 'matriz_permisos',
        ));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     */
    private function containsEntry($entries, string $entity, int $entityId): bool
    {
        return $entries->contains(
            static fn (array $entry): bool => $entry['entidad'] === $entity
                && $entry['entidad_id'] === $entityId,
        );
    }
}
