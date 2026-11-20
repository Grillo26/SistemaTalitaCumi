<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\BitacoraEntry;

final class ParameterUpdateTest extends ConfiguracionTestCase
{
    public function test_superadmin_updates_integer_and_audits(): void
    {
        $user = $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 45, [
                'confirmado' => true,
                'motivo' => 'Ajuste operativo aprobado por dirección',
            ]),
        )
            ->assertOk()
            ->assertJsonPath('data.valor', 45)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('can.update', true);

        $this->assertDatabaseHas('configuracion', [
            'id' => $parametro->id,
            'valor' => '45',
            'version' => 2,
        ]);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('entidad_id', $parametro->id)
            ->where('accion', 'EDITAR')
            ->sole();

        $this->assertSame($user->id, $audit->usuario_id);
        $this->assertSame('50', $audit->datos_anteriores['valor']);
        $this->assertSame('45', $audit->datos_nuevos['valor']);
        $this->assertSame('Ajuste operativo aprobado por dirección', $audit->motivo);
        $this->assertNull($audit->sucursal_id);
    }

    public function test_rejects_invalid_type(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 'cuarenta', ['confirmado' => true, 'motivo' => 'Prueba']),
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor');
    }

    public function test_rejects_value_out_of_range_with_clear_message(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $response = $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 20, ['confirmado' => true, 'motivo' => 'Prueba']),
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor');

        $this->assertStringContainsString('30', $response->json('errors.valor.0'));
        $this->assertStringContainsString('90', $response->json('errors.valor.0'));
    }

    public function test_rejects_incoherent_turn_hours(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('agenda.turno_manana.fin');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('agenda.turno_manana.fin', '07:30'),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'PARAMETRO_INCOHERENTE');
    }

    public function test_rejects_session_duration_longer_than_agenda_block(): void
    {
        $this->actingAsSuperadmin();
        $finManana = $this->parametro('agenda.turno_manana.fin');
        $duracion = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$finManana->id}",
            ['valor' => '09:00', 'version' => $finManana->version],
        )->assertOk();

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$duracion->id}",
            [
                'valor' => 90,
                'version' => $duracion->version,
                'confirmado' => true,
                'motivo' => 'Prueba de coherencia',
            ],
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'PARAMETRO_INCOHERENTE');
    }

    public function test_sensitive_parameter_requires_confirmation_and_reason(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            ['valor' => 45, 'version' => $parametro->version],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confirmado', 'motivo']);

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            ['valor' => 45, 'version' => $parametro->version, 'confirmado' => true, 'motivo' => '   '],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_sensitive_parameter_updates_with_confirmation_and_reason(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('zona_horaria');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('zona_horaria', 'America/Argentina/Buenos_Aires', [
                'confirmado' => true,
                'motivo' => 'Convenio con sede de Buenos Aires',
            ]),
        )
            ->assertOk()
            ->assertJsonPath('data.valor', 'America/Argentina/Buenos_Aires');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('entidad_id', $parametro->id)
            ->where('accion', 'EDITAR')
            ->sole();

        $this->assertSame('Convenio con sede de Buenos Aires', $audit->motivo);
    }

    public function test_rejects_version_conflict(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            [
                'valor' => 45,
                'version' => $parametro->version + 10,
                'confirmado' => true,
                'motivo' => 'Prueba',
            ],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PARAMETRO_VERSION_CONFLICT');
    }

    public function test_non_editable_parameters_are_rejected(): void
    {
        $this->actingAsSuperadmin();
        $moneda = $this->parametro('moneda');
        $leyenda = $this->parametro('comprobante.leyenda_no_factura');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$moneda->id}",
            ['valor' => 'USD', 'version' => $moneda->version],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$leyenda->id}",
            ['valor' => 'Otra leyenda', 'version' => $leyenda->version],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('valor');
    }

    public function test_no_op_update_does_not_increment_version_or_audit(): void
    {
        $this->actingAsSuperadmin();
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            [
                'valor' => 50,
                'version' => $parametro->version,
                'confirmado' => true,
                'motivo' => 'Sin cambios',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.version', 1);

        $this->assertSame(0, BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('entidad_id', $parametro->id)
            ->where('accion', 'EDITAR')
            ->count());
    }

    public function test_administracion_and_recepcion_cannot_update(): void
    {
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->actingAsRole('administracion');
        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            ['valor' => 45, 'version' => 1],
        )->assertForbidden();

        $this->actingAsRole('recepcion');
        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            ['valor' => 45, 'version' => 1],
        )->assertForbidden();
    }
}
