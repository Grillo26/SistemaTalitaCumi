<?php

namespace Tests\Feature\Modules\Seguridad\Auditoria;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class AuditQueryTest extends SeguridadTestCase
{
    public function test_superadmin_sees_all_scoped_and_global_events(): void
    {
        [$adminA, $adminB, $branchA, $branchB, $rowA, $rowB, $global, $userRow] = $this->fixtures();
        $superadmin = $this->superadmin();

        Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/seguridad/bitacora?per_page=50')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => [
                    'id', 'usuario', 'modulo', 'modulo_label', 'entidad',
                    'entidad_label', 'accion', 'accion_label', 'resultado',
                    'resultado_label', 'critico', 'created_at',
                ]],
                'meta',
                'can',
            ]);

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($rowA->id, $ids);
        $this->assertContains($rowB->id, $ids);
        $this->assertContains($global->id, $ids);
        $this->assertContains($userRow->id, $ids);
    }

    public function test_administracion_only_sees_events_of_its_branches(): void
    {
        [$adminA, , $branchA, , $rowA, $rowB, $global, $userRow] = $this->fixtures();

        Sanctum::actingAs($adminA);

        $response = $this->getJson('/api/seguridad/bitacora?per_page=50')->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($rowA->id, $ids);
        $this->assertNotContains($rowB->id, $ids);
        $this->assertNotContains($global->id, $ids);
        $this->assertNotContains($userRow->id, $ids);
        $this->assertSame(1, $response->json('meta.total'));
    }

    public function test_detail_is_scoped_and_can_return_not_found(): void
    {
        [$adminA, , , , $rowA, $rowB, $global] = $this->fixtures();

        Sanctum::actingAs($adminA);

        $this->getJson("/api/seguridad/bitacora/{$rowA->id}")
            ->assertOk()
            ->assertJsonPath('data.entidad', 'sala');

        $this->getJson("/api/seguridad/bitacora/{$rowB->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'AUDIT_ACCESS_DENIED');

        $this->getJson("/api/seguridad/bitacora/{$global->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'AUDIT_ACCESS_DENIED');

        $this->getJson('/api/seguridad/bitacora/999999')
            ->assertStatus(404)
            ->assertJsonPath('code', 'AUDIT_ENTRY_NOT_FOUND');
    }

    public function test_filters_by_module_entity_action_result_critical_user_branch_and_pagination(): void
    {
        [, , $branchA, , $rowA, , , $userRow] = $this->fixtures();
        $superadmin = $this->superadmin();

        Sanctum::actingAs($superadmin);

        $byModule = $this->getJson('/api/seguridad/bitacora?modulo=organizacion&per_page=50')->assertOk();
        $moduleIds = collect($byModule->json('data'))->pluck('id')->all();
        $this->assertContains($rowA->id, $moduleIds);

        $byEntity = $this->getJson('/api/seguridad/bitacora?entidad=usuario&per_page=50')->assertOk();
        $this->assertContains($userRow->id, collect($byEntity->json('data'))->pluck('id')->all());

        $byAction = $this->getJson('/api/seguridad/bitacora?accion=DESACTIVAR&per_page=50')->assertOk();
        $this->assertContains($userRow->id, collect($byAction->json('data'))->pluck('id')->all());

        $byCritical = $this->getJson('/api/seguridad/bitacora?critico=1&per_page=50')->assertOk();
        $this->assertContains($userRow->id, collect($byCritical->json('data'))->pluck('id')->all());
        $this->assertNotContains($rowA->id, collect($byCritical->json('data'))->pluck('id')->all());

        $byUser = $this->getJson("/api/seguridad/bitacora?usuario_id={$userRow->usuario_id}&per_page=50")->assertOk();
        $this->assertContains($userRow->id, collect($byUser->json('data'))->pluck('id')->all());

        $byBranch = $this->getJson("/api/seguridad/bitacora?sucursal_id={$branchA->id}&per_page=50")->assertOk();
        $this->assertContains($rowA->id, collect($byBranch->json('data'))->pluck('id')->all());

        $paged = $this->getJson('/api/seguridad/bitacora?per_page=1')->assertOk();
        $this->assertCount(1, $paged->json('data'));
        $this->assertSame(1, $paged->json('meta.per_page'));
    }

    public function test_date_range_is_validated(): void
    {
        Sanctum::actingAs($this->superadmin());

        $this->getJson('/api/seguridad/bitacora?fecha_desde=2026-01-02&fecha_hasta=2026-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha_hasta');
    }

    public function test_query_is_audited(): void
    {
        $superadmin = $this->superadmin();
        $before = BitacoraEntry::query()
            ->where('entidad', 'bitacora')
            ->where('accion', 'CONSULTAR')
            ->count();

        Sanctum::actingAs($superadmin);

        $this->getJson('/api/seguridad/bitacora')->assertOk();

        $after = BitacoraEntry::query()
            ->where('entidad', 'bitacora')
            ->where('accion', 'CONSULTAR')
            ->count();

        $this->assertSame($before + 1, $after);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'bitacora')
            ->where('accion', 'CONSULTAR')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($superadmin->id, $entry->usuario_id);
        $this->assertSame('auditoria', $entry->modulo);
        $this->assertNull($entry->sucursal_id);
    }

    public function test_filter_options_are_scoped_and_include_catalog(): void
    {
        [$adminA, $adminB] = $this->fixtures();
        $superadmin = $this->superadmin();

        Sanctum::actingAs($superadmin);

        $response = $this->getJson('/api/seguridad/bitacora/opciones')
            ->assertOk()
            ->assertJsonPath('data.retencion_anios', 5)
            ->assertJsonCount(3, 'data.resultados');

        $moduleKeys = collect($response->json('data.modulos'))->pluck('clave')->all();
        $entityKeys = collect($response->json('data.entidades'))->pluck('clave')->all();
        $actionKeys = collect($response->json('data.acciones'))->pluck('clave')->all();

        $this->assertContains('organizacion', $moduleKeys);
        $this->assertContains('usuarios', $moduleKeys);
        $this->assertContains('usuario', $entityKeys);
        $this->assertContains('CREAR', $actionKeys);
        $this->assertContains('DESACTIVAR', $actionKeys);

        $userIds = collect($response->json('data.usuarios'))->pluck('id')->all();
        $this->assertContains($adminA->id, $userIds);
        $this->assertContains($adminB->id, $userIds);

        Sanctum::actingAs($adminA);

        $scoped = $this->getJson('/api/seguridad/bitacora/opciones')->assertOk();
        $scopedUserIds = collect($scoped->json('data.usuarios'))->pluck('id')->all();

        $this->assertContains($adminA->id, $scopedUserIds);
        $this->assertNotContains($adminB->id, $scopedUserIds);
    }

    public function test_recepcion_has_no_access(): void
    {
        $branch = $this->branch();
        $reception = $this->managedUser('recepcion', $branch);

        Sanctum::actingAs($reception);

        $this->getJson('/api/seguridad/bitacora')->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: Branch, 3: Branch, 4: BitacoraEntry, 5: BitacoraEntry, 6: BitacoraEntry, 7: BitacoraEntry}
     */
    private function fixtures(): array
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);
        $adminA = $this->managedUser('administracion', $branchA);
        $adminB = $this->managedUser('administracion', $branchB);

        $rowA = $this->auditRow([
            'usuario_id' => $adminA->id,
            'sucursal_id' => $branchA->id,
            'accion' => 'CREAR',
            'entidad' => 'sala',
            'entidad_id' => 1,
            'datos_nuevos' => ['nombre' => 'Sala A'],
            'modulo' => 'organizacion',
            'resultado' => 'exito',
            'critico' => false,
            'ip' => '203.0.113.1',
        ]);

        $rowB = $this->auditRow([
            'usuario_id' => $adminB->id,
            'sucursal_id' => $branchB->id,
            'accion' => 'CREAR',
            'entidad' => 'sala',
            'entidad_id' => 2,
            'datos_nuevos' => ['nombre' => 'Sala B'],
            'modulo' => 'organizacion',
            'resultado' => 'exito',
            'critico' => false,
            'ip' => '203.0.113.2',
        ]);

        $global = $this->auditRow([
            'usuario_id' => $adminA->id,
            'sucursal_id' => null,
            'accion' => 'INICIAR_SESION',
            'entidad' => 'sesion',
            'entidad_id' => null,
            'modulo' => 'autenticacion',
            'resultado' => 'exito',
            'critico' => false,
            'ip' => '203.0.113.3',
        ]);

        $userRow = $this->auditRow([
            'usuario_id' => $adminA->id,
            'sucursal_id' => null,
            'accion' => 'DESACTIVAR',
            'entidad' => 'usuario',
            'entidad_id' => 99,
            'datos_nuevos' => ['activo' => false],
            'modulo' => 'usuarios',
            'resultado' => 'exito',
            'critico' => true,
            'ip' => '203.0.113.4',
        ]);

        return [$adminA, $adminB, $branchA, $branchB, $rowA, $rowB, $global, $userRow];
    }
}
