<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Events\InscripcionRetirada;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use Illuminate\Support\Facades\Event;

final class EstadosPacienteTest extends InscripcionesAtencionesTestCase
{
    public function test_pause_and_reactivate_patient_with_reason_and_history(): void
    {
        $escenario = $this->scenario('administracion');

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/pausar', [
            'estado' => 'en_pausa',
            'estado_esperado' => 'activo',
            'motivo' => 'viaje',
            'motivo_detalle' => 'Viaje familiar',
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_pausa');

        $this->assertDatabaseHas('paciente', [
            'id' => $escenario['paciente']->id,
            'estado' => 'en_pausa',
        ]);

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/reactivar', [
            'estado' => 'activo',
            'estado_esperado' => 'en_pausa',
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', 'activo');

        $this->getJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/historial')
            ->assertOk()
            ->assertJsonPath('data.0.estado_nuevo', 'en_pausa')
            ->assertJsonPath('data.1.estado_nuevo', 'activo');
    }

    public function test_patient_list_includes_principal_tutor(): void
    {
        $escenario = $this->scenario('administracion');

        $this->getJson('/api/inscripciones-atenciones/estados/pacientes?search='.$escenario['paciente']->codigo)
            ->assertOk()
            ->assertJsonPath('data.0.tutor_principal', $escenario['tutor']->nombre_completo)
            ->assertJsonPath('data.0.sucursal_registro.nombre', $escenario['branch']->nombre);
    }

    public function test_patient_pause_requires_reason(): void
    {
        $escenario = $this->scenario('administracion');

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/pausar', [
            'estado' => 'en_pausa',
            'estado_esperado' => 'activo',
        ])->assertStatus(422);
    }

    public function test_reception_cannot_change_patient_state(): void
    {
        $escenario = $this->scenario('recepcion');

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/pausar', [
            'estado' => 'en_pausa',
            'estado_esperado' => 'activo',
            'motivo' => 'viaje',
        ])->assertForbidden();
    }

    public function test_patient_retirement_cascades_active_and_paused_inscriptions(): void
    {
        $escenario = $this->scenario('administracion');
        $primera = $this->crearInscripcion($escenario)->assertCreated()->json('data');
        $segunda = $this->crearInscripcion(
            array_merge($escenario, ['sala' => $this->sala($escenario['branch'])]),
        )->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/estados/inscripciones/inscripcion_sala/'.$segunda['id'].'/pausar',
            ['estado_esperado' => 'activa', 'motivo' => 'viaje'],
        )->assertOk();

        $preview = $this->getJson(
            '/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/retiro/preview',
        )
            ->assertOk()
            ->assertJsonPath('data.conteos.total', 2)
            ->json('data');

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/retirar', [
            'estado_esperado' => 'activo',
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
            'inscripciones' => array_map(
                static fn (array $item): array => [
                    'tipo_entidad' => $item['tipo_entidad'],
                    'id' => $item['id'],
                    'estado_esperado' => $item['estado_efectivo'],
                ],
                $preview['inscripciones'],
            ),
        ])
            ->assertOk()
            ->assertJsonPath('data.paciente.estado', 'retirado')
            ->assertJsonCount(2, 'data.inscripciones');

        $this->assertDatabaseHas('paciente', [
            'id' => $escenario['paciente']->id,
            'estado' => 'retirado',
        ]);
        $this->assertDatabaseHas('inscripcion_sala', ['id' => $primera['id'], 'estado' => 'retirada']);
        $this->assertDatabaseHas('inscripcion_sala', ['id' => $segunda['id'], 'estado' => 'retirada']);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'paciente',
            'entidad_id' => $escenario['paciente']->id,
            'accion' => 'CAMBIAR_ESTADO',
        ]);
        $this->assertSame(
            2,
            BitacoraEntry::query()
                ->where('entidad', 'inscripcion_sala')
                ->whereIn('entidad_id', [$primera['id'], $segunda['id']])
                ->where('accion', 'RETIRAR')
                ->count(),
        );
    }

    public function test_patient_retirement_precheck_reports_unavailable_without_blocking(): void
    {
        $escenario = $this->scenario('administracion');
        $this->crearInscripcion($escenario)->assertCreated();

        $this->getJson(
            '/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/retiro/prechequeo',
        )
            ->assertOk()
            ->assertJsonPath('data.prechequeo.turnos.disponible', true)
            ->assertJsonPath('data.prechequeo.pago.disponible', false)
            ->assertJsonPath('data.preview.conteos.total', 1);
    }

    public function test_cascade_rolls_back_all_when_one_inscription_fails(): void
    {
        $escenario = $this->scenario('administracion');
        $primera = $this->crearInscripcion($escenario)->assertCreated()->json('data');
        $segunda = $this->crearInscripcion(
            array_merge($escenario, ['sala' => $this->sala($escenario['branch'])]),
        )->assertCreated()->json('data');

        Event::listen(InscripcionRetirada::class, static function () use ($segunda): void {
            InscripcionSala::query()->whereKey($segunda['id'])->update(['estado' => 'retirada']);
        });

        $preview = $this->getJson(
            '/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/retiro/preview',
        )->assertOk()->json('data');

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/retirar', [
            'estado_esperado' => 'activo',
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
            'inscripciones' => array_map(
                static fn (array $item): array => [
                    'tipo_entidad' => $item['tipo_entidad'],
                    'id' => $item['id'],
                    'estado_esperado' => $item['estado_efectivo'],
                ],
                $preview['inscripciones'],
            ),
        ])->assertStatus(409);

        $this->assertDatabaseHas('paciente', [
            'id' => $escenario['paciente']->id,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('inscripcion_sala', ['id' => $primera['id'], 'estado' => 'activa']);
        $this->assertDatabaseHas('inscripcion_sala', ['id' => $segunda['id'], 'estado' => 'activa']);
    }

    public function test_cascade_blocks_when_an_active_inscription_is_outside_scope(): void
    {
        $escenario = $this->scenario('administracion');
        $this->crearInscripcion($escenario)->assertCreated();

        $otraSucursal = $this->branch();
        $otraSala = $this->sala($otraSucursal);
        $otroServicio = $this->servicioSalaGrupal();
        $otraCategoria = $this->categoriaTarifa();
        $otraTarifa = $this->tarifa($otroServicio, $otraSucursal, $otraCategoria);

        InscripcionSala::query()->create([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sala_id' => $otraSala->id,
            'sucursal_id' => $otraSucursal->id,
            'servicio_id' => $otroServicio->id,
            'tarifa_id' => $otraTarifa->id,
            'categoria_tarifa_id' => $otraCategoria->id,
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
            'monto_esperado' => '500.00',
            'estado' => 'activa',
        ]);

        $this->getJson(
            '/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/retiro/preview',
        )
            ->assertStatus(403)
            ->assertJsonPath('code', 'RETIRO_FUERA_DE_SUCURSAL');
    }

    public function test_stale_state_is_rejected(): void
    {
        $escenario = $this->scenario('administracion');

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/pausar', [
            'estado' => 'en_pausa',
            'estado_esperado' => 'en_pausa',
            'motivo' => 'viaje',
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESTADO_DESACTUALIZADO');
    }
}
