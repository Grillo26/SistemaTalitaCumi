<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Events\InscripcionCreated;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRenewed;
use Illuminate\Support\Facades\Event;

final class InscripcionRenewTest extends InscripcionesAtencionesTestCase
{
    public function test_preview_proposes_next_period(): void
    {
        $scenario = $this->scenario();
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/renovacion')
            ->assertOk()
            ->assertJsonPath('data.fecha_inicio', '2026-12-01')
            ->assertJsonPath('data.fecha_fin', '2026-12-31')
            ->assertJsonPath('data.tarifa_disponible', true)
            ->assertJsonPath('data.tarifa.monto', '500.00');
    }

    public function test_renews_and_links_to_origin(): void
    {
        $scenario = $this->scenario();
        $origin = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $response = $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales/'.$origin['id'].'/renovar',
            [],
        )->assertCreated();

        $response
            ->assertJsonPath('data.inscripcion_origen_id', $origin['id'])
            ->assertJsonPath('data.fecha_inicio', '2026-12-01')
            ->assertJsonPath('data.fecha_fin', '2026-12-31')
            ->assertJsonPath('data.monto_esperado', '500.00')
            ->assertJsonPath('data.estado', 'activa');

        $newId = $response->json('data.id');

        $this->assertDatabaseHas('inscripcion_sala', [
            'id' => $newId,
            'inscripcion_origen_id' => $origin['id'],
        ]);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'inscripcion_sala',
            'entidad_id' => $newId,
            'accion' => 'RENOVAR',
        ]);
    }

    public function test_renewal_does_not_emit_created_event(): void
    {
        $scenario = $this->scenario();
        $origin = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        Event::fake([InscripcionCreated::class, InscripcionRenewed::class]);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales/'.$origin['id'].'/renovar',
            [],
        )->assertCreated();

        Event::assertDispatched(InscripcionRenewed::class);
        Event::assertNotDispatched(InscripcionCreated::class);
    }

    public function test_blocks_second_active_renewal(): void
    {
        $scenario = $this->scenario();
        $origin = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$origin['id'].'/renovar', [])
            ->assertCreated();

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$origin['id'].'/renovar', [])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_YA_RENOVADA');
    }

    public function test_blocks_renewal_of_withdrawn_inscription(): void
    {
        $scenario = $this->scenario('administracion');
        $origin = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$origin['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertOk();

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$origin['id'].'/renovar', [])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_RETIRADA');
    }
}
