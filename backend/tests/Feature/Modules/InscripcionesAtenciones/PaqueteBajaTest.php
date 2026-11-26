<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;

final class PaqueteBajaTest extends PaquetesSesionesTestCase
{
    public function test_withdraws_with_motive_and_keeps_the_record(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'cambio_servicio',
            'motivo_detalle' => 'Pasa a fonoaudiología',
            'fecha_baja' => '2026-11-24',
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', 'retirado')
            ->assertJsonPath('data.estado_mostrado', 'retirado')
            ->assertJsonPath('data.retiro.motivo_codigo', 'cambio_servicio')
            ->assertJsonPath('data.retiro.motivo_detalle', 'Pasa a fonoaudiología')
            ->assertJsonPath('data.retiro.fecha_baja', '2026-11-24');

        $this->assertDatabaseHas('paquete_sesiones', [
            'id' => $creado['id'],
            'estado' => 'retirado',
        ]);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'paquete_sesiones')
            ->where('entidad_id', $creado['id'])
            ->where('accion', 'RETIRAR')
            ->firstOrFail();

        $this->assertTrue((bool) $audit->critico);
        $this->assertSame('Cambio de servicio: Pasa a fonoaudiología', $audit->motivo);
        $this->assertSame('cambio_servicio', $audit->datos_nuevos['motivo_codigo']);
        $this->assertSame('2026-11-24', $audit->datos_nuevos['fecha_baja']);
    }

    public function test_rejects_otro_motive_without_detail(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'otro',
            'fecha_baja' => '2026-11-25',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'MOTIVO_BAJA_PAQUETE_INVALIDO');

        $this->assertSame(
            'activo',
            PaqueteSesiones::query()->findOrFail($creado['id'])->estado->value,
        );
    }

    public function test_rejects_future_withdrawal_date(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-26',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha_baja');
    }

    public function test_blocks_withdrawing_twice(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $payload = [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ];

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', $payload)
            ->assertOk();

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAQUETE_RETIRADO');
    }

    public function test_physical_delete_is_not_allowed(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->deleteJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAQUETE_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('paquete_sesiones', ['id' => $creado['id']]);
    }

    public function test_reception_cannot_withdraw(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $creado = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$creado['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertForbidden();
    }
}
