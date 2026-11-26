<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use Spatie\Permission\Models\Role;

final class InscripcionModulesTest extends InscripcionesAtencionesTestCase
{
    public function test_ficha_works_while_payment_and_attendance_are_unavailable(): void
    {
        $scenario = $this->scenario();
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('can.pago_disponible', false)
            ->assertJsonPath('can.asistencia_disponible', false)
            ->assertJsonPath('can.turnos_futuros_disponible', false)
            ->assertJsonPath('can.pdf', false)
            ->assertJsonPath('can.export', false);
    }

    public function test_payment_endpoint_returns_clear_module_unavailable_error(): void
    {
        $scenario = $this->scenario();
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/pago')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'Pagos y caja');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/pago')
            ->assertStatus(503)
            ->assertJsonPath('modulo', 'Pagos y caja');
    }

    public function test_attendance_and_future_turns_endpoints_are_unavailable(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/asistencia')
            ->assertStatus(503)
            ->assertJsonPath('modulo', 'Agenda y turnos');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/turnos-futuros')
            ->assertStatus(503)
            ->assertJsonPath('modulo', 'Agenda y turnos');
    }

    public function test_receipt_data_uses_center_configuration_and_legend(): void
    {
        $scenario = $this->scenario();
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/comprobante')
            ->assertOk()
            ->assertJsonPath('data.centro.nombre', 'TalitaCumi')
            ->assertJsonPath('data.leyenda_no_factura', 'Este documento NO es factura')
            ->assertJsonPath('data.inscripcion.id', $created['id'])
            ->assertJsonPath('data.inscripcion.monto_esperado', '500.00');
    }

    public function test_pdf_and_export_are_pending_infrastructure(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/comprobante/pdf')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'PDF');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/exportar')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE');
    }

    public function test_permission_and_audit_catalogs_are_seeded(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'inscripcion.ver']);
        $this->assertDatabaseHas('permissions', ['name' => 'inscripcion.editar']);
        $this->assertDatabaseHas('accion_auditada', [
            'modulo' => 'inscripciones',
            'entidad' => 'inscripcion_sala',
            'accion' => 'RETIRAR',
            'critico' => true,
        ]);

        $recepcion = Role::findByName('recepcion', 'web');
        $administracion = Role::findByName('administracion', 'web');

        $this->assertTrue($recepcion->hasPermissionTo('inscripcion.renovar'));
        $this->assertFalse($recepcion->hasPermissionTo('inscripcion.editar'));
        $this->assertFalse($recepcion->hasPermissionTo('inscripcion.retirar'));
        $this->assertTrue($administracion->hasPermissionTo('inscripcion.editar'));
        $this->assertTrue($administracion->hasPermissionTo('inscripcion.retirar'));
    }
}
