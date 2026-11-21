<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Exceptions\ParameterSnapshotNotFoundException;
use App\Modules\Organizacion\Queries\GetParameterSnapshotAtDateQuery;

final class ParameterHistoryTest extends ConfiguracionTestCase
{
    public function test_history_lists_initial_and_edited_entries_with_user(): void
    {
        $user = $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 45, [
                'confirmado' => true,
                'motivo' => 'Ajuste de agenda',
            ]),
        )->assertOk();

        $response = $this->getJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}/historial",
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.accion', 'EDITAR')
            ->assertJsonPath('data.0.motivo', 'Ajuste de agenda')
            ->assertJsonPath('data.0.usuario.nombre', $user->name)
            ->assertJsonPath('data.1.accion', 'CREAR')
            ->assertJsonPath('data.1.usuario', null);

        $this->assertSame('50', $response->json('data.1.datos_nuevos.valor'));
        $this->assertSame('45', $response->json('data.0.datos_nuevos.valor'));
    }

    public function test_administracion_can_read_history(): void
    {
        $this->actingAsRole('administracion');
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->getJson("/api/organizacion/configuracion/parametros/{$parametro->id}/historial")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_recepcion_cannot_read_history(): void
    {
        $this->actingAsRole('recepcion');
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->getJson("/api/organizacion/configuracion/parametros/{$parametro->id}/historial")
            ->assertForbidden();
    }

    public function test_snapshot_reconstructs_current_and_past_configuration(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');
        $query = app(GetParameterSnapshotAtDateQuery::class);

        $this->assertSame('50', $query($parametro->id, now())['valor']);

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 45, [
                'confirmado' => true,
                'motivo' => 'Ajuste de agenda',
            ]),
        )->assertOk();

        $this->assertSame('45', $query($parametro->id, now())['valor']);

        $this->expectException(ParameterSnapshotNotFoundException::class);

        $query($parametro->id, now()->subYear());
    }
}
