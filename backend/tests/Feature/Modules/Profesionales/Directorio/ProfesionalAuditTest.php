<?php

namespace Tests\Feature\Modules\Profesionales\Directorio;

use App\Modules\Profesionales\Models\BitacoraEntry;
use App\Modules\Profesionales\Models\Especialidad;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfesionalAuditTest extends ProfesionalesTestCase
{
    public function test_professional_events_are_replicated_per_active_branch(): void
    {
        $this->actingAsSuperadmin();
        $branchA = $this->branch();
        $branchB = $this->branch();

        $created = $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload())
            ->assertCreated()
            ->json('data');

        $id = (int) $created['id'];

        $this->postJson("/api/profesionales/profesionales/{$id}/sucursales", ['sucursal_id' => $branchA->id])->assertOk();
        $this->postJson("/api/profesionales/profesionales/{$id}/sucursales", ['sucursal_id' => $branchB->id])->assertOk();

        $this->patchJson("/api/profesionales/profesionales/{$id}", [
            'telefono' => '70001111',
            'version' => 1,
        ])->assertOk();

        $rows = BitacoraEntry::query()
            ->where('entidad', 'profesional')
            ->where('entidad_id', $id)
            ->where('accion', 'ACTUALIZAR')
            ->orderBy('sucursal_id')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame([$branchA->id, $branchB->id], $rows->pluck('sucursal_id')->map(static fn ($id): int => (int) $id)->all());
        $this->assertSame('profesionales', $rows->first()->modulo);
        $this->assertSame('exito', $rows->first()->resultado);
        $this->assertNotNull($rows->first()->ip);
        $this->assertArrayHasKey('telefono', $rows->first()->datos_nuevos);
        $this->assertArrayNotHasKey('password', $rows->first()->datos_nuevos);
    }

    public function test_deactivation_is_critical_and_keeps_reason(): void
    {
        $this->actingAsSuperadmin();
        $branch = $this->branch();
        $profesional = $this->profesional(['version' => 1]);
        $this->assignBranch($profesional, $branch);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/desactivar", [
            'motivo' => 'Licencia médica',
            'version' => 1,
        ])->assertOk();

        $row = BitacoraEntry::query()
            ->where('entidad', 'profesional')
            ->where('entidad_id', $profesional->id)
            ->where('accion', 'DESACTIVAR')
            ->firstOrFail();

        $this->assertTrue($row->critico);
        $this->assertSame('Licencia médica', $row->motivo);
        $this->assertSame($branch->id, $row->sucursal_id);
    }

    public function test_specialty_assignment_change_has_its_own_audit_entity(): void
    {
        $this->actingAsSuperadmin();
        $branch = $this->branch();
        $principal = $this->especialidad('Psicología');
        $new = Especialidad::factory()->create(['nombre' => 'Fisioterapia Auditoría']);
        $profesional = $this->profesional([], $principal);
        $this->assignBranch($profesional, $branch);

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'especialidad_principal_id' => $new->id,
            'especialidades_secundarias' => [],
            'version' => 1,
        ])->assertOk();

        $row = BitacoraEntry::query()
            ->where('entidad', 'profesional_especialidad')
            ->where('entidad_id', $profesional->id)
            ->firstOrFail();

        $this->assertSame($branch->id, $row->sucursal_id);
        $this->assertNotEmpty($row->datos_anteriores);
        $this->assertNotEmpty($row->datos_nuevos);
        $this->assertSame('principal', $row->datos_nuevos[0]['tipo']);
    }

    public function test_specialty_catalog_events_are_global(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/profesionales/especialidades', [
            'nombre' => 'Especialidad Auditoría',
            'descripcion' => 'Prueba',
            'activo' => true,
        ])->assertCreated();

        $row = BitacoraEntry::query()
            ->where('entidad', 'especialidad')
            ->where('accion', 'CREAR')
            ->latest('id')
            ->firstOrFail();

        $this->assertNull($row->sucursal_id);
        $this->assertSame('profesionales', $row->modulo);
    }

    public function test_audited_action_catalog_contains_module_entities(): void
    {
        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'profesionales',
            'entidad' => 'profesional',
            'accion' => 'DESACTIVAR',
            'critico' => true,
        ]);

        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'profesionales',
            'entidad' => 'profesional_sucursal',
            'accion' => 'CREAR',
        ]);
    }
}
