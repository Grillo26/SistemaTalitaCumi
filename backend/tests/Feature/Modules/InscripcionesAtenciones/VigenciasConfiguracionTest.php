<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class VigenciasConfiguracionTest extends VigenciasTestCase
{
    public function test_admin_can_read_and_update_alert_window_without_hardcoding(): void
    {
        $this->actingAsRole('administracion');

        $detalle = $this->getJson('/api/inscripciones-atenciones/vigencias/configuracion/ventana')
            ->assertOk()
            ->assertJsonPath('data.valor', 7)
            ->json('data');

        $this->patchJson(
            '/api/inscripciones-atenciones/vigencias/configuracion/ventana',
            ['valor' => 2, 'version' => $detalle['version']],
        )
            ->assertOk()
            ->assertJsonPath('data.valor', 2);

        $this->assertDatabaseHas('configuracion', [
            'clave' => 'vigencia.ventana_alerta_dias',
            'valor' => '2',
        ]);
    }

    public function test_invalid_window_is_rejected(): void
    {
        $this->actingAsRole('administracion');
        $detalle = $this->getJson('/api/inscripciones-atenciones/vigencias/configuracion/ventana')
            ->assertOk()
            ->json('data');

        $this->patchJson(
            '/api/inscripciones-atenciones/vigencias/configuracion/ventana',
            ['valor' => 0, 'version' => $detalle['version']],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor');
    }

    public function test_reception_cannot_configure_window(): void
    {
        $this->actingAsRole('recepcion');

        $this->getJson('/api/inscripciones-atenciones/vigencias/configuracion/ventana')
            ->assertForbidden();
    }
}
