<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use App\Modules\Seguridad\Exceptions\AuditUnavailableException;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

/**
 * 07.05/RF-006 / 07.05/RF-011 / 07.05/RF-014 / 07.05/RN-008 /
 * 07.05/CA-002 / 07.05/CA-005 / 07.05/CA-009 / 07.05/CA-010.
 */
final class DiagnosticoCoordinacionTest extends DiagnosticosTestCase
{
    public function test_detalle_muestra_puestos_turnos_estado_y_whatsapp(): void
    {
        $escenario = $this->escenarioDiagnostico(2);
        $diagnostico = $escenario['diagnostico'];
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->crearTurnoDiagnostico(
            $diagnostico,
            (int) $profesional->id,
            '2025-12-02',
            '09:00',
            '09:50',
        );

        $response = $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}"))
            ->assertOk()
            ->assertJsonPath('data.id', $diagnostico->id)
            ->assertJsonPath('data.estado_general', 'incompleto')
            ->assertJsonCount(2, 'data.puestos')
            ->assertJsonCount(1, 'data.turnos')
            ->assertJsonPath('data.turnos.0.estado', 'programado')
            ->assertJsonFragment(['tipo' => 'sin_turno'])
            ->assertJsonPath('data.whatsapp.disponible', true);

        $telefono = $response->json('data.whatsapp.telefono');
        $url = $response->json('data.whatsapp.whatsapp_url');

        $this->assertIsString($telefono);
        $this->assertStringStartsWith('https://wa.me/591', (string) $url);
        $this->assertStringNotContainsString(
            (string) $escenario['tipo']->nombre,
            (string) $response->json('data.whatsapp.mensaje'),
        );

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'atencion_diagnostico',
            'entidad_id' => $diagnostico->id,
            'accion' => 'CONSULTAR',
        ]);
    }

    public function test_sin_permiso_de_contacto_no_expone_telefono_ni_enlace(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];
        $this->actorConPermisos($escenario['branch'], ['asignacion.ver', 'atencion.ver']);

        $response = $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}"))
            ->assertOk()
            ->assertJsonPath('data.whatsapp.disponible', false);

        $this->assertNull($response->json('data.whatsapp.telefono'));
        $this->assertNull($response->json('data.whatsapp.whatsapp_url'));

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}/whatsapp"))
            ->assertForbidden();
    }

    public function test_whatsapp_requiere_turnos_programados_y_permiso(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}/whatsapp"))
            ->assertStatus(409)
            ->assertJsonPath('code', 'WHATSAPP_DIAGNOSTICO_NO_DISPONIBLE');

        $this->crearTurnoDiagnostico($diagnostico, (int) $profesional->id);

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}/whatsapp"))
            ->assertOk()
            ->assertJsonPath('data.disponible', true)
            ->assertJsonStructure(['data' => ['telefono', 'whatsapp_url', 'mensaje']]);
    }

    public function test_listado_filtra_por_estado_tipo_y_profesional(): void
    {
        $escenario = $this->escenarioDiagnostico(2);
        $diagnostico = $escenario['diagnostico'];
        $primero = $escenario['profesionales_diagnostico'][0];
        $segundo = $escenario['profesionales_diagnostico'][1];

        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $primero->id, '2025-12-02', '09:00'),
        )->assertCreated();
        $this->postJson(
            $this->diagnosticosUrl("/{$diagnostico->id}/turnos"),
            $this->payloadProgramacion((int) $segundo->id, '2025-12-02', '10:00'),
        )->assertCreated();

        $this->getJson($this->diagnosticosUrl('?desde=2025-12-01&hasta=2025-12-31&estado_general=en_proceso'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $diagnostico->id)
            ->assertJsonPath('data.0.estado_general', 'en_proceso');

        $this->getJson($this->diagnosticosUrl('?desde=2025-12-01&hasta=2025-12-31&profesional_id='.$primero->id))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson($this->diagnosticosUrl('?desde=2025-12-01&hasta=2025-12-31&tipo_diagnostico_id='.$escenario['tipo']->id))
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson($this->diagnosticosUrl('?desde=2026-01-01&hasta=2026-01-31'))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_listado_no_audita_la_consulta(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $antes = BitacoraEntry::query()
            ->where('accion', 'CONSULTAR')
            ->where('entidad', 'atencion_diagnostico')
            ->count();

        $this->getJson($this->diagnosticosUrl('?desde=2025-11-01&hasta=2025-12-31'))
            ->assertOk();

        $despues = BitacoraEntry::query()
            ->where('accion', 'CONSULTAR')
            ->where('entidad', 'atencion_diagnostico')
            ->count();

        $this->assertSame($antes, $despues);
        $this->assertSame($escenario['diagnostico']->id, $this->getJson(
            $this->diagnosticosUrl('?desde=2025-11-01&hasta=2025-12-31'),
        )->json('data.0.id'));
    }

    public function test_otra_sucursal_responde_404_en_detalle_y_403_en_listado(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $otraSucursal = $this->branch();
        $otroPaciente = $this->paciente($otraSucursal);
        $otroDiagnostico = $this->crearDiagnosticoEnSucursal(
            $otraSucursal,
            $otroPaciente,
            (int) $escenario['servicio_diagnostico']->id,
            $escenario['tipo'],
            $escenario['especialidades'],
            $escenario['profesionales_diagnostico'],
        );

        $this->getJson($this->diagnosticosUrl("/{$otroDiagnostico->id}"))
            ->assertNotFound()
            ->assertJsonPath('code', 'DIAGNOSTICO_NO_ENCONTRADO');

        $this->getJson($this->diagnosticosUrl('?sucursal_id='.$otraSucursal->id))
            ->assertForbidden();

        $this->getJson($this->diagnosticosUrl('?desde=2025-11-01&hasta=2025-12-31'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $escenario['diagnostico']->id);
    }

    public function test_detalle_falla_cerrado_si_la_auditoria_no_esta_disponible(): void
    {
        $escenario = $this->escenarioDiagnostico(1);

        $this->mock(AuditRecorderContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registrarConsultaSensible')
                ->once()
                ->andThrow(new AuditUnavailableException);
        });

        $this->getJson($this->diagnosticosUrl("/{$escenario['diagnostico']->id}"))
            ->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');
    }

    public function test_opciones_respeta_alcance_y_devuelve_catalogos(): void
    {
        $escenario = $this->escenarioDiagnostico(1);

        $this->getJson($this->diagnosticosUrl('/opciones'))
            ->assertOk()
            ->assertJsonPath('data.sucursal_id', $escenario['branch']->id)
            ->assertJsonCount(1, 'data.sucursales')
            ->assertJsonCount(4, 'data.estados')
            ->assertJsonStructure([
                'data' => [
                    'tipos_diagnostico',
                    'profesionales',
                    'estados',
                ],
            ]);
    }

    public function test_detalle_carga_cinco_profesionales_sin_n_plus_one(): void
    {
        $escenario = $this->escenarioDiagnostico(5);
        $diagnostico = $escenario['diagnostico'];

        foreach ($escenario['profesionales_diagnostico'] as $indice => $profesional) {
            $this->crearTurnoDiagnostico(
                $diagnostico,
                (int) $profesional->id,
                '2025-12-0'.($indice + 2),
                '09:00',
                '09:50',
            );
        }

        $consultas = 0;
        DB::listen(function () use (&$consultas): void {
            $consultas++;
        });

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}"))
            ->assertOk()
            ->assertJsonCount(5, 'data.puestos')
            ->assertJsonCount(5, 'data.turnos');

        $this->assertLessThanOrEqual(22, $consultas);
    }

    public function test_falto_se_muestra_en_el_puesto_y_habilita_programar_reemplazo(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $diagnostico = $escenario['diagnostico'];
        $profesional = $escenario['profesionales_diagnostico'][0];

        $this->crearTurnoDiagnostico(
            $diagnostico,
            (int) $profesional->id,
            '2025-12-02',
            '09:00',
            '09:50',
            EstadoTurno::FALTO,
        );

        $this->getJson($this->diagnosticosUrl("/{$diagnostico->id}"))
            ->assertOk()
            ->assertJsonPath('data.estado_general', 'incompleto')
            ->assertJsonPath('data.profesionales.0.turno_vigente', null)
            ->assertJsonPath('data.profesionales.0.turno_actual.estado', 'falto')
            ->assertJsonPath('data.profesionales.0.disponible_para_programar', true)
            ->assertJsonPath('data.puestos.0.profesionales.0.turno.estado', 'falto')
            ->assertJsonPath('data.faltantes.0.tipo', 'sin_turno');
    }

    public function test_permiso_de_ver_es_requerido(): void
    {
        $escenario = $this->escenarioDiagnostico(1);
        $this->actorConPermisos($escenario['branch'], ['atencion.ver']);

        $this->getJson($this->diagnosticosUrl("/{$escenario['diagnostico']->id}"))
            ->assertForbidden();
        $this->getJson($this->diagnosticosUrl())
            ->assertForbidden();
    }
}
