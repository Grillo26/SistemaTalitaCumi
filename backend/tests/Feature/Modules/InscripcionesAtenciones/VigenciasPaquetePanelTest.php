<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteBatchContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteResumen;

final class VigenciasPaquetePanelTest extends VigenciasTestCase
{
    public function test_package_panel_shows_no_available_balance_without_agenda(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
        ])->assertCreated();

        $this->getJson('/api/inscripciones-atenciones/vigencias/panel?tipo_entidad=paquete_sesiones')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tipo_entidad', 'paquete_sesiones')
            ->assertJsonPath('data.0.estado_vigencia', 'vencido')
            ->assertJsonPath('data.0.saldo_disponible', false);
    }

    public function test_user_with_only_package_permission_only_sees_packages(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
        ])->assertCreated();

        $this->actingAsWithPermissions(
            ['vigencia.ver', 'paquete.ver'],
            $escenario['branch'],
        );

        $this->getJson('/api/inscripciones-atenciones/vigencias/panel')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.tipo_entidad', 'paquete_sesiones');
    }

    public function test_package_panel_uses_batch_consumption_when_available(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
        ])->assertCreated();

        $this->app->instance(
            ConsumoPaqueteBatchContract::class,
            new class implements ConsumoPaqueteBatchContract
            {
                public function disponible(): bool
                {
                    return true;
                }

                public function resumenes(array $paqueteIds): array
                {
                    $resultado = [];

                    foreach ($paqueteIds as $id) {
                        $resultado[$id] = new ConsumoPaqueteResumen(
                            pactadas: 10,
                            realizadas: 4,
                            faltadas: 1,
                            canceladas: 2,
                        );
                    }

                    return $resultado;
                }
            },
        );

        $this->getJson('/api/inscripciones-atenciones/vigencias/panel?tipo_entidad=paquete_sesiones')
            ->assertOk()
            ->assertJsonPath('data.0.saldo_disponible', true)
            ->assertJsonPath('data.0.saldo', 5);
    }
}
