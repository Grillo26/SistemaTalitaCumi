<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosCacheKeys;
use Illuminate\Support\Facades\Cache;

final class VigenciasRenovacionTest extends VigenciasTestCase
{
    public function test_preview_exposes_previous_and_current_tariff_and_renews_via_origin_action(): void
    {
        $escenario = $this->scenario('administracion');
        $origen = $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');

        $preview = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovacion',
        )
            ->assertOk()
            ->assertJsonPath('data.tipo_entidad', 'inscripcion_sala')
            ->assertJsonPath('data.cambio_tarifa', false)
            ->assertJsonPath('data.tarifa_anterior.monto', '500.00')
            ->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovar',
            [
                'fecha_inicio' => $preview['fecha_inicio'],
                'fecha_fin' => $preview['fecha_fin'],
                'monto_visto' => $preview['monto_vigente'],
                'tarifa_id_vista' => $preview['tarifa_vigente']['tarifa_id'],
            ],
        )
            ->assertCreated()
            ->assertJsonPath('data.entidad_origen_id', $origen['id'])
            ->assertJsonPath('data.monto_esperado', '500.00');

        $this->assertDatabaseHas('inscripcion_sala', [
            'inscripcion_origen_id' => $origen['id'],
            'monto_esperado' => 500.00,
        ]);

        $this->getJson(
            '/api/inscripciones-atenciones/vigencias/panel?estado=renovado',
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $origen['id']);
    }

    public function test_renewal_requires_confirmation_when_tariff_changed(): void
    {
        $escenario = $this->scenario('administracion');
        $origen = $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');

        Tarifa::query()->whereKey($escenario['tarifa']->id)->update(['monto' => '650.00']);
        Cache::forget(CatalogoServiciosCacheKeys::tarifas());

        $preview = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovacion',
        )
            ->assertOk()
            ->assertJsonPath('data.cambio_tarifa', true)
            ->assertJsonPath('data.monto_anterior', '500.00')
            ->assertJsonPath('data.monto_vigente', '650.00')
            ->json('data');

        $payload = [
            'fecha_inicio' => $preview['fecha_inicio'],
            'fecha_fin' => $preview['fecha_fin'],
            'monto_visto' => $preview['monto_vigente'],
            'tarifa_id_vista' => $preview['tarifa_vigente']['tarifa_id'],
        ];

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovar',
            $payload,
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'CAMBIO_TARIFA_SIN_CONFIRMAR');

        $payload['confirmar_tarifa_cambio'] = true;

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovar',
            $payload,
        )->assertCreated();
    }

    public function test_execution_rejects_tariff_that_changes_after_preview(): void
    {
        $escenario = $this->scenario('administracion');
        $origen = $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');

        $preview = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovacion',
        )->assertOk()->json('data');

        Tarifa::query()->whereKey($escenario['tarifa']->id)->update(['monto' => '700.00']);
        Cache::forget(CatalogoServiciosCacheKeys::tarifas());

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovar',
            [
                'fecha_inicio' => $preview['fecha_inicio'],
                'fecha_fin' => $preview['fecha_fin'],
                'monto_visto' => $preview['monto_vigente'],
                'tarifa_id_vista' => $preview['tarifa_vigente']['tarifa_id'],
                'confirmar_tarifa_cambio' => true,
            ],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'CAMBIO_TARIFA_SIN_CONFIRMAR');

        $this->assertDatabaseCount('inscripcion_sala', 1);
    }

    public function test_package_renewal_does_not_transfer_sessions(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $origen = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
            'cantidad_sesiones' => 10,
        ])->assertCreated()->json('data');

        $preview = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/paquete_sesiones/'.$origen['id'].'/renovacion',
        )
            ->assertOk()
            ->assertJsonPath('data.cantidad_sesiones', 10)
            ->assertJsonPath('data.tarifa_anterior.monto', '500.00')
            ->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/entidades/paquete_sesiones/'.$origen['id'].'/renovar',
            [
                'fecha_inicio' => $preview['fecha_inicio'],
                'fecha_fin' => $preview['fecha_fin'],
                'monto_visto' => $preview['monto_vigente'],
                'tarifa_id_vista' => $preview['tarifa_vigente']['tarifa_id'],
            ],
        )
            ->assertCreated()
            ->assertJsonPath('data.entidad_origen_id', $origen['id']);

        $this->assertDatabaseHas('paquete_sesiones', [
            'id' => $origen['id'],
            'cantidad_sesiones' => 10,
            'estado' => 'activo',
        ]);
        $this->assertDatabaseHas('paquete_sesiones', [
            'paquete_origen_id' => $origen['id'],
            'cantidad_sesiones' => 10,
        ]);
    }

    public function test_renewal_requires_underlying_permission(): void
    {
        $escenario = $this->scenario('administracion');
        $origen = $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');

        $branch = $escenario['branch'];
        $user = $this->actingAsWithPermissions(['vigencia.ver', 'inscripcion.ver'], $branch);

        $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$origen['id'].'/renovacion',
        )->assertForbidden();

        $this->assertNotNull($user->id);
    }
}
