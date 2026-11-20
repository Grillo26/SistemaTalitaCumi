<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\BitacoraEntry;

final class ParameterRestoreTest extends ConfiguracionTestCase
{
    public function test_restores_default_value_and_audits(): void
    {
        $user = $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 45, [
                'confirmado' => true,
                'motivo' => 'Cambio temporal',
            ]),
        )->assertOk();

        $parametro->refresh();

        $this->postJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}/restaurar",
            [
                'version' => $parametro->version,
                'confirmado' => true,
                'motivo' => 'Volver al valor acordado',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.valor', 50)
            ->assertJsonPath('data.version', 3);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('entidad_id', $parametro->id)
            ->where('accion', 'RESTAURAR')
            ->sole();

        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame('45', $audit->datos_anteriores['valor']);
        $this->assertSame('50', $audit->datos_nuevos['valor']);
        $this->assertSame('Volver al valor acordado', $audit->motivo);
    }

    public function test_restore_is_rejected_when_value_is_already_default(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->postJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}/restaurar",
            ['version' => $parametro->version, 'confirmado' => true, 'motivo' => 'Prueba'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PARAMETRO_YA_EN_DEFECTO');
    }

    public function test_sensitive_restore_requires_reason(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('zona_horaria');

        $this->postJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}/restaurar",
            ['version' => $parametro->version, 'confirmado' => true],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_administracion_cannot_restore(): void
    {
        $this->actingAsRole('administracion');
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->postJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}/restaurar",
            ['version' => $parametro->version],
        )->assertForbidden();
    }
}
