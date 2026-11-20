<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

final class ConfiguracionAuthorizationTest extends ConfiguracionTestCase
{
    public function test_administracion_is_read_only(): void
    {
        $this->actingAsRole('administracion');
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->getJson('/api/organizacion/configuracion/parametros')->assertOk();
        $this->getJson("/api/organizacion/configuracion/parametros/{$parametro->id}")->assertOk();
        $this->getJson("/api/organizacion/configuracion/parametros/{$parametro->id}/historial")->assertOk();

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            ['valor' => 45, 'version' => $parametro->version],
        )->assertForbidden();
    }

    public function test_recepcion_only_reads_operational_parameters(): void
    {
        $this->actingAsRole('recepcion');
        $visible = $this->parametro('duracion_sesion_minutos');
        $hidden = $this->parametro('comprobante.formato');

        $this->getJson("/api/organizacion/configuracion/parametros/{$visible->id}")->assertOk();
        $this->getJson("/api/organizacion/configuracion/parametros/{$hidden->id}")->assertForbidden();
        $this->getJson("/api/organizacion/configuracion/parametros/{$visible->id}/historial")->assertForbidden();
    }

    public function test_export_permission_is_reserved_and_not_exposed(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/configuracion/parametros')
            ->assertOk()
            ->assertJsonPath('can.export', false);

        $this->getJson('/api/organizacion/configuracion/parametros/exportar')
            ->assertNotFound();
    }
}
