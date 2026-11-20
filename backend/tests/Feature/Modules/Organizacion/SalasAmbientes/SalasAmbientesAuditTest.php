<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Models\BitacoraEntry;

/**
 * RF-015 / RNF-013: entidades y alcance en bitácora.
 */
final class SalasAmbientesAuditTest extends SalasAmbientesTestCase
{
    public function test_catalog_and_space_events_use_expected_entities_and_branch_scope(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tipoId = $this->postJson('/api/organizacion/salas-ambientes/tipos-sala', [
            'nombre' => 'Tipo Auditado',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/organizacion/salas-ambientes/tipos-sala/{$tipoId}/desactivar", [
            'motivo' => 'Fin de prueba',
            'version' => 1,
        ])->assertOk();

        $turnoId = $this->postJson('/api/organizacion/salas-ambientes/turnos-catalogo', [
            'nombre' => 'Turno Auditado',
            'orden' => 10,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/organizacion/salas-ambientes/turnos-catalogo/{$turnoId}/desactivar", [
            'motivo' => 'Fin de prueba turno',
            'version' => 1,
        ])->assertOk();

        $salaId = $this->postJson('/api/organizacion/salas-ambientes/salas', $this->salaPayload($branch, [
            'nombre' => 'Sala Auditada',
        ]))->assertCreated()->json('data.id');

        $this->postJson("/api/organizacion/salas-ambientes/salas/{$salaId}/desactivar", [
            'motivo' => 'Fin de prueba sala',
            'version' => 1,
        ])->assertOk();

        $ambienteId = $this->postJson('/api/organizacion/salas-ambientes/ambientes', $this->ambientePayload($branch, [
            'nombre' => 'Ambiente Auditado',
        ]))->assertCreated()->json('data.id');

        $this->postJson("/api/organizacion/salas-ambientes/ambientes/{$ambienteId}/desactivar", [
            'motivo' => 'Fin de prueba ambiente',
            'version' => 1,
        ])->assertOk();

        $tipoRows = BitacoraEntry::query()->where('entidad', 'tipo_sala')->orderBy('id')->get();
        $this->assertCount(2, $tipoRows);
        $this->assertSame(['CREAR', 'DESACTIVAR'], $tipoRows->pluck('accion')->all());
        $this->assertNull($tipoRows[0]->sucursal_id);
        $this->assertSame('Fin de prueba', $tipoRows[1]->motivo);

        $turnoRows = BitacoraEntry::query()->where('entidad', 'turno_catalogo')->orderBy('id')->get();
        $this->assertCount(2, $turnoRows);
        $this->assertNull($turnoRows[0]->sucursal_id);
        $this->assertSame('Fin de prueba turno', $turnoRows[1]->motivo);

        $salaRows = BitacoraEntry::query()->where('entidad', 'sala')->orderBy('id')->get();
        $this->assertCount(2, $salaRows);
        $this->assertSame($branch->id, $salaRows[0]->sucursal_id);
        $this->assertSame('Fin de prueba sala', $salaRows[1]->motivo);
        $this->assertTrue($salaRows[1]->datos_anteriores['activo']);
        $this->assertFalse($salaRows[1]->datos_nuevos['activo']);

        $ambienteRows = BitacoraEntry::query()->where('entidad', 'ambiente')->orderBy('id')->get();
        $this->assertCount(2, $ambienteRows);
        $this->assertSame($branch->id, $ambienteRows[0]->sucursal_id);
        $this->assertSame('Fin de prueba ambiente', $ambienteRows[1]->motivo);
    }
}
