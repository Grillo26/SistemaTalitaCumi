<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;

final class InscripcionBajaTest extends InscripcionesAtencionesTestCase
{
    public function test_withdraws_with_motive_date_and_audit(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'motivo_detalle' => 'La familia se muda de ciudad.',
            'fecha_baja' => '2026-11-25',
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', 'retirada')
            ->assertJsonPath('data.estado_label', 'Retirada')
            ->assertJsonPath('data.retiro.motivo_codigo', 'retiro_paciente')
            ->assertJsonPath('data.retiro.fecha_baja', '2026-11-25');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'inscripcion_sala')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'RETIRAR')
            ->firstOrFail();

        $this->assertTrue($audit->critico);
        $this->assertSame('retiro_paciente', $audit->datos_nuevos['motivo_codigo']);
        $this->assertSame('2026-11-25', $audit->datos_nuevos['fecha_baja']);
        $this->assertStringContainsString('Retiro del paciente', (string) $audit->motivo);
    }

    public function test_other_motive_requires_detail(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/retirar', [
            'motivo' => 'otro',
            'fecha_baja' => '2026-11-25',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'MOTIVO_BAJA_INVALIDO');
    }

    public function test_future_withdraw_date_is_rejected(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-12-01',
        ])->assertStatus(422)->assertJsonValidationErrors('fecha_baja');
    }

    public function test_delete_is_always_rejected(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->deleteJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('inscripcion_sala', ['id' => $created['id']]);
        $this->assertSame(1, InscripcionSala::query()->count());
    }
}
