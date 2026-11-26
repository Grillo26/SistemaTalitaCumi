<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Events\PaqueteCreated;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRenewed;
use Illuminate\Support\Facades\Event;

final class PaqueteRenewTest extends PaquetesSesionesTestCase
{
    public function test_preview_proposes_next_period_with_current_tariff(): void
    {
        $escenario = $this->escenarioPaquete();
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/renovacion')
            ->assertOk()
            ->assertJsonPath('data.paquete_origen_id', $creado['id'])
            ->assertJsonPath('data.fecha_inicio', '2026-12-26')
            ->assertJsonPath('data.fecha_fin', '2027-01-24')
            ->assertJsonPath('data.cantidad_sesiones', 10)
            ->assertJsonPath('data.tarifa_disponible', true)
            ->assertJsonPath('data.tarifa.monto', '50.00')
            ->assertJsonPath('data.monto_total', '500.00');
    }

    public function test_renews_and_links_to_origin(): void
    {
        $escenario = $this->escenarioPaquete();
        $origen = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $response = $this->postJson(
            '/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar',
            [],
        )->assertCreated();

        $response
            ->assertJsonPath('data.paquete_origen_id', $origen['id'])
            ->assertJsonPath('data.fecha_inicio', '2026-12-26')
            ->assertJsonPath('data.fecha_fin', '2027-01-24')
            ->assertJsonPath('data.monto_esperado', '500.00')
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.origen.id', $origen['id']);

        $nuevoId = $response->json('data.id');

        $this->assertDatabaseHas('paquete_sesiones', [
            'id' => $nuevoId,
            'paquete_origen_id' => $origen['id'],
        ]);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'paquete_sesiones',
            'entidad_id' => $nuevoId,
            'accion' => 'RENOVAR',
        ]);
    }

    public function test_renewal_does_not_emit_created_event(): void
    {
        $escenario = $this->escenarioPaquete();
        $origen = $this->crearPaquete($escenario)->assertCreated()->json('data');

        Event::fake([PaqueteCreated::class, PaqueteRenewed::class]);

        $this->postJson(
            '/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar',
            [],
        )->assertCreated();

        Event::assertDispatched(PaqueteRenewed::class);
        Event::assertNotDispatched(PaqueteCreated::class);
    }

    public function test_allows_adjusting_days_hour_professional_and_quantity(): void
    {
        $escenario = $this->escenarioPaquete();
        $origen = $this->crearPaquete($escenario)->assertCreated()->json('data');
        $otro = $this->profesionalEnSucursal($escenario['branch']->id);

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar', [
            'cantidad_sesiones' => 5,
            'dias_semana' => [2, 5],
            'hora' => '10:15',
            'profesional_id' => $otro->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.cantidad_sesiones', 5)
            ->assertJsonPath('data.monto_esperado', '250.00')
            ->assertJsonPath('data.dias_semana', [2, 5])
            ->assertJsonPath('data.hora', '10:15')
            ->assertJsonPath('data.profesional.id', $otro->id);
    }

    public function test_blocks_second_renewal(): void
    {
        $escenario = $this->escenarioPaquete();
        $origen = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $primera = $this->postJson(
            '/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar',
            [],
        )->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar',
            [],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAQUETE_YA_RENOVADO')
            ->assertJsonPath('renovacion_id', $primera['id']);
    }

    public function test_blocks_renewal_of_withdrawn_package(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $origen = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertOk();

        $this->postJson(
            '/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar',
            [],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAQUETE_RETIRADO');
    }

    public function test_requires_both_dates_when_adjusting_period(): void
    {
        $escenario = $this->escenarioPaquete();
        $origen = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$origen['id'].'/renovar', [
            'fecha_inicio' => '2026-12-26',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha_fin');
    }
}
