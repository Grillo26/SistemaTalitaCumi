<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;

final class EstadosInscripcionTest extends PaquetesSesionesTestCase
{
    public function test_pause_and_reactivate_inscription_with_motive_and_history(): void
    {
        $escenario = $this->scenario('recepcion');
        $creada = $this->crearInscripcion($escenario)->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/pausar',
            [
                'estado_esperado' => 'activa',
                'motivo' => 'viaje',
                'motivo_detalle' => 'Viaje familiar',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_pausa');

        $this->assertDatabaseHas('inscripcion_sala', [
            'id' => $creada['id'],
            'estado' => 'en_pausa',
        ]);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'inscripcion_sala')
            ->where('entidad_id', $creada['id'])
            ->where('accion', 'PAUSAR')
            ->firstOrFail();

        $this->assertSame('viaje', $audit->datos_nuevos['motivo_codigo']);

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/reactivar',
            ['estado_esperado' => 'en_pausa'],
        )
            ->assertOk()
            ->assertJsonPath('data.estado_efectivo', 'activa');

        $this->assertDatabaseHas('inscripcion_sala', [
            'id' => $creada['id'],
            'estado' => 'activa',
        ]);

        $this->getJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/historial',
        )
            ->assertOk()
            ->assertJsonPath('data.1.accion', 'PAUSAR')
            ->assertJsonPath('data.2.accion', 'REACTIVAR');
    }

    public function test_pause_requires_motive(): void
    {
        $escenario = $this->scenario('recepcion');
        $creada = $this->crearInscripcion($escenario)->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/pausar',
            ['estado_esperado' => 'activa'],
        )->assertStatus(422)->assertJsonValidationErrors('motivo');
    }

    public function test_vencida_cannot_be_paused(): void
    {
        $escenario = $this->scenario('recepcion');
        $creada = $this->crearInscripcion($escenario)->assertCreated()->json('data');
        InscripcionSala::query()->whereKey($creada['id'])->update(['fecha_fin' => '2026-11-20']);

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/pausar',
            ['estado_esperado' => 'vencida', 'motivo' => 'viaje'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_VENCIDA_NO_PAUSABLE');
    }

    public function test_admin_can_retire_a_paused_inscription_and_reception_cannot(): void
    {
        $escenario = $this->scenario('administracion');
        $creada = $this->crearInscripcion($escenario)->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/pausar',
            ['estado_esperado' => 'activa', 'motivo' => 'viaje'],
        )->assertOk();

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/retirar',
            [
                'estado_esperado' => 'en_pausa',
                'motivo' => 'retiro_paciente',
                'fecha_baja' => '2026-11-25',
            ],
        )->assertOk();

        $this->assertDatabaseHas('inscripcion_sala', [
            'id' => $creada['id'],
            'estado' => 'retirada',
        ]);

        $otra = $this->crearInscripcion(
            array_merge($escenario, ['sala' => $this->sala($escenario['branch'])]),
        )->assertCreated()->json('data');
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $escenario['branch']);

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$otra['id'].'/retirar',
            [
                'estado_esperado' => 'activa',
                'motivo' => 'retiro_paciente',
                'fecha_baja' => '2026-11-25',
            ],
        )->assertForbidden();
    }

    public function test_retire_vencida_is_rejected(): void
    {
        $escenario = $this->scenario('administracion');
        $creada = $this->crearInscripcion($escenario)->assertCreated()->json('data');
        InscripcionSala::query()->whereKey($creada['id'])->update(['fecha_fin' => '2026-11-20']);

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/retirar',
            [
                'estado_esperado' => 'vencida',
                'motivo' => 'retiro_paciente',
                'fecha_baja' => '2026-11-25',
            ],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_VENCIDA_NO_RETIRABLE');
    }

    public function test_precheck_stub_is_available_false_and_does_not_block_pause(): void
    {
        $escenario = $this->scenario('recepcion');
        $creada = $this->crearInscripcion($escenario)->assertCreated()->json('data');

        $this->getJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/prechequeo-pausa',
        )
            ->assertOk()
            ->assertJsonPath('data.turnos.disponible', false)
            ->assertJsonPath('data.pago', null);

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$creada['id'].'/pausar',
            ['estado_esperado' => 'activa', 'motivo' => 'viaje'],
        )->assertOk();
    }

    public function test_package_can_be_paused_reactivated_and_retired_from_pause(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $paquete = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ])->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/paquete_sesiones/'.$paquete['id'].'/pausar',
            ['estado_esperado' => 'activa', 'motivo' => 'viaje'],
        )->assertOk()->assertJsonPath('data.estado', 'en_pausa');

        $this->assertDatabaseHas('paquete_sesiones', ['id' => $paquete['id'], 'estado' => 'en_pausa']);

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/paquete_sesiones/'.$paquete['id'].'/reactivar',
            ['estado_esperado' => 'en_pausa'],
        )->assertOk()->assertJsonPath('data.estado_efectivo', 'activa');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/paquete_sesiones/'.$paquete['id'].'/pausar',
            ['estado_esperado' => 'activa', 'motivo' => 'enfermedad'],
        )->assertOk();

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/paquete_sesiones/'.$paquete['id'].'/retirar',
            [
                'estado_esperado' => 'en_pausa',
                'motivo' => 'retiro_paciente',
                'fecha_baja' => '2026-11-25',
            ],
        )->assertOk();

        $this->assertDatabaseHas('paquete_sesiones', ['id' => $paquete['id'], 'estado' => 'retirado']);
    }

    public function test_index_filters_effective_state_and_branch(): void
    {
        $escenario = $this->scenario('recepcion');
        $activa = $this->crearInscripcion($escenario)->assertCreated()->json('data');
        $pausada = $this->crearInscripcion(
            array_merge($escenario, ['sala' => $this->sala($escenario['branch'])]),
        )->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$pausada['id'].'/pausar',
            ['estado_esperado' => 'activa', 'motivo' => 'enfermedad'],
        )->assertOk();

        $this->getJson('/api/inscripciones-atenciones/estados/inscripciones?estado=en_pausa')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $pausada['id']);

        $this->getJson(
            '/api/inscripciones-atenciones/estados/inscripciones?sucursal_id='.$escenario['branch']->id,
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->assertNotSame($activa['id'], $pausada['id']);
    }
}
