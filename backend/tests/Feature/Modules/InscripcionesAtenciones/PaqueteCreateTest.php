<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use Illuminate\Support\Facades\Cache;

final class PaqueteCreateTest extends PaquetesSesionesTestCase
{
    public function test_preview_calculates_total_and_defaults(): void
    {
        $escenario = $this->escenarioPaquete();

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/opciones')
            ->assertOk()
            ->assertJsonPath('data.defaults.cantidad_sesiones_default', 10)
            ->assertJsonPath('data.defaults.duracion_minutos_default', 50);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/monto-esperado?'
            .http_build_query([
                'servicio_id' => $escenario['servicio']->id,
                'sucursal_id' => $escenario['branch']->id,
                'categoria_tarifa_id' => $escenario['categoria']->id,
                'fecha_inicio' => '2026-11-26',
                'cantidad_sesiones' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('data.monto_sesion', '50.00')
            ->assertJsonPath('data.monto_total', '500.00')
            ->assertJsonPath('data.cantidad_sesiones', 10);
    }

    public function test_creates_package_without_professional(): void
    {
        $escenario = $this->escenarioPaquete();

        $this->crearPaquete($escenario, ['profesional_id' => null])
            ->assertCreated()
            ->assertJsonPath('data.profesional', null)
            ->assertJsonPath('data.estado', 'activo');
    }

    public function test_blocks_duplicate_same_service_and_period(): void
    {
        $escenario = $this->escenarioPaquete();

        $this->crearPaquete($escenario)->assertCreated();

        $this->crearPaquete($escenario)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAQUETE_DUPLICADO');
    }

    public function test_allows_same_period_for_another_service(): void
    {
        $escenario = $this->escenarioPaquete();
        $this->crearPaquete($escenario)->assertCreated();

        $otroServicio = $this->servicioTerapia(['nombre' => 'Fonoaudiología individual']);
        $otraCategoria = $this->categoriaTarifa(['nombre' => 'General 2']);
        $this->tarifa($otroServicio, $escenario['branch'], $otraCategoria, '40.00');
        Cache::flush();

        $this->crearPaquete($escenario, [
            'servicio_id' => $otroServicio->id,
            'categoria_tarifa_id' => $otraCategoria->id,
        ])->assertCreated()->assertJsonPath('data.monto_esperado', '400.00');
    }

    public function test_blocks_inactive_patient(): void
    {
        $escenario = $this->escenarioPaquete();
        $escenario['paciente']->update(['estado' => 'retirado']);

        $this->crearPaquete($escenario)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE');
    }

    public function test_blocks_service_that_is_not_terapia_individual(): void
    {
        $escenario = $this->escenarioPaquete();
        $tipoEntrevista = TipoServicio::query()
            ->where('codigo', 'entrevista')
            ->firstOrFail();

        $servicio = $this->servicioTerapia([
            'tipo_servicio_id' => $tipoEntrevista->id,
            'nombre' => 'Entrevista de prueba',
        ]);
        $categoria = $this->categoriaTarifa(['nombre' => 'General 3']);
        $this->tarifa($servicio, $escenario['branch'], $categoria, '50.00');

        $this->crearPaquete($escenario, [
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SERVICIO_NO_ES_TERAPIA_INDIVIDUAL');
    }

    public function test_blocks_professional_from_another_branch(): void
    {
        $escenario = $this->escenarioPaquete();
        $otraSucursal = $this->branch();
        $profesional = $this->profesionalEnSucursal($otraSucursal->id);

        $this->crearPaquete($escenario, ['profesional_id' => $profesional->id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PROFESIONAL_NO_DISPONIBLE');
    }

    public function test_blocks_branch_outside_scope(): void
    {
        $escenario = $this->escenarioPaquete();
        $otraSucursal = $this->branch();
        $this->withoutBranch($escenario['user'], $escenario['branch']);

        $this->crearPaquete($escenario, ['sucursal_id' => $otraSucursal->id])
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_client_cannot_send_total_amount(): void
    {
        $escenario = $this->escenarioPaquete();

        $this->crearPaquete($escenario, ['monto_esperado' => '1.00'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('monto_esperado');
    }

    public function test_requires_confirmation_when_tariff_changed(): void
    {
        $escenario = $this->escenarioPaquete();
        $payload = $this->payloadPaquete($escenario, ['tarifa_id_esperada' => 999999]);

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_CAMBIO');

        $payload['confirmar_tarifa_cambio'] = true;

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones', $payload)
            ->assertCreated()
            ->assertJsonPath('data.monto_esperado', '500.00');
    }

    public function test_validates_days_hour_and_duration(): void
    {
        $escenario = $this->escenarioPaquete();

        $this->crearPaquete($escenario, ['dias_semana' => [1, 1]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('dias_semana.1');

        $this->crearPaquete($escenario, ['dias_semana' => [0, 7]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('dias_semana.0');

        $this->crearPaquete($escenario, ['hora' => '25:99'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hora');

        $this->crearPaquete($escenario, ['duracion_minutos' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('duracion_minutos');
    }

    public function test_does_not_use_client_amount_even_when_service_changes(): void
    {
        $escenario = $this->escenarioPaquete();

        $response = $this->crearPaquete($escenario, ['monto_esperado' => '1.00'])
            ->assertStatus(422);

        $this->assertSame(0, PaqueteSesiones::query()->count());
    }
}
