<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class PaqueteModulesTest extends PaquetesSesionesTestCase
{
    public function test_ficha_works_while_optional_modules_are_unavailable(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertOk()
            ->assertJsonPath('can.pago_disponible', false)
            ->assertJsonPath('can.consumo_disponible', false)
            ->assertJsonPath('can.turnos_futuros_disponible', true)
            ->assertJsonPath('can.pdf', false)
            ->assertJsonPath('can.export', false);
    }

    public function test_consumo_uses_fake_contract_and_computes_balance(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->fakeConsumo($escenario, realizadas: 4, faltadas: 2, canceladas: 3);

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/consumo')
            ->assertOk()
            ->assertJsonPath('data.pactadas', 10)
            ->assertJsonPath('data.realizadas', 4)
            ->assertJsonPath('data.faltadas', 2)
            ->assertJsonPath('data.canceladas', 3)
            ->assertJsonPath('data.saldo', 4);
    }

    public function test_consumo_is_unavailable_until_agenda_exists(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/consumo')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'Agenda y turnos');
    }

    public function test_payment_endpoints_return_clear_module_unavailable_error(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/pago')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'Pagos y caja');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/pago')
            ->assertStatus(503)
            ->assertJsonPath('modulo', 'Pagos y caja');
    }

    public function test_future_turns_endpoint_returns_count(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/turnos-futuros')
            ->assertOk()
            ->assertJsonPath('data.cantidad', 0)
            ->assertJsonPath('data.fechas', []);
    }

    public function test_receipt_data_uses_center_configuration(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/comprobante')
            ->assertOk()
            ->assertJsonPath('data.centro.nombre', 'TalitaCumi')
            ->assertJsonPath('data.leyenda_no_factura', 'Este documento NO es factura')
            ->assertJsonPath('data.paquete.id', $creado['id'])
            ->assertJsonPath('data.paquete.monto_esperado', '500.00')
            ->assertJsonPath('data.paquete.paciente.id', $escenario['paciente']->id);
    }

    public function test_pdf_and_export_are_pending_infrastructure(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/comprobante/pdf')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'PDF');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/exportar')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE');
    }

    public function test_options_return_configured_defaults(): void
    {
        $this->escenarioPaquete('recepcion');

        $this->getJson('/api/inscripciones-atenciones/paquetes-sesiones/opciones')
            ->assertOk()
            ->assertJsonPath('data.defaults.cantidad_sesiones_default', 10)
            ->assertJsonPath('data.defaults.duracion_minutos_default', 50);
    }
}
