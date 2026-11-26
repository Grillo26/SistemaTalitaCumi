<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;

final class PaqueteUpdateTest extends PaquetesSesionesTestCase
{
    public function test_updates_pattern_and_records_audit_before_after(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'dias_semana' => [2, 4],
            'hora' => '09:30',
            'observaciones' => 'Ajuste administrativo',
        ])
            ->assertOk()
            ->assertJsonPath('data.dias_semana', [2, 4])
            ->assertJsonPath('data.dias_semana_labels', ['Martes', 'Jueves'])
            ->assertJsonPath('data.hora', '09:30')
            ->assertJsonPath('data.observaciones', 'Ajuste administrativo');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'paquete_sesiones')
            ->where('entidad_id', $creado['id'])
            ->where('accion', 'ACTUALIZAR')
            ->firstOrFail();

        $this->assertSame('08:00', $audit->datos_anteriores['hora']);
        $this->assertSame('09:30', $audit->datos_nuevos['hora']);
        $this->assertSame([1, 3], $audit->datos_anteriores['dias_semana']);
        $this->assertSame([2, 4], $audit->datos_nuevos['dias_semana']);
    }

    public function test_updates_professional_and_allows_clearing_it(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $otro = $this->profesionalEnSucursal($escenario['branch']->id);

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'profesional_id' => $otro->id,
            'dias_semana' => [1, 3],
            'hora' => '08:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.profesional.id', $otro->id);

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'profesional_id' => null,
            'dias_semana' => [1, 3],
            'hora' => '08:00',
        ])
            ->assertOk()
            ->assertJsonPath('data.profesional', null);
    }

    public function test_rejects_immutable_fields(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'dias_semana' => [1, 3],
            'hora' => '08:00',
            'cantidad_sesiones' => 20,
            'fecha_inicio' => '2026-11-01',
            'monto_esperado' => '1.00',
            'estado' => 'retirado',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'cantidad_sesiones',
                'fecha_inicio',
                'monto_esperado',
                'estado',
            ]);
    }

    public function test_rejects_professional_from_another_branch(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');
        $otraSucursal = $this->branch();
        $profesional = $this->profesionalEnSucursal($otraSucursal->id);

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'profesional_id' => $profesional->id,
            'dias_semana' => [1, 3],
            'hora' => '08:00',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PROFESIONAL_NO_DISPONIBLE');
    }

    public function test_blocks_edition_of_withdrawn_package(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertOk();

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'], [
            'dias_semana' => [2],
            'hora' => '10:00',
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAQUETE_RETIRADO');
    }

    public function test_returns_not_found_for_unknown_package(): void
    {
        $this->escenarioPaquete('administracion');

        $this->patchJson('/api/inscripciones-atenciones/paquetes-sesiones/999999', [
            'dias_semana' => [1],
            'hora' => '08:00',
        ])
            ->assertStatus(404)
            ->assertJsonPath('code', 'PAQUETE_NOT_FOUND');
    }
}
