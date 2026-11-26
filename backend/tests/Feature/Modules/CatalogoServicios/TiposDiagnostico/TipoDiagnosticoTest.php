<?php

namespace Tests\Feature\Modules\CatalogoServicios\TiposDiagnostico;

use App\Modules\CatalogoServicios\Contracts\TipoDiagnosticoUsageContract;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\Profesionales\Contracts\ProfessionalDiagnosticRequirementsContract;
use LogicException;

final class TipoDiagnosticoTest extends TiposDiagnosticoTestCase
{
    public function test_superadmin_creates_diagnostic_type_with_specialties_and_audits(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación integral');
        $psicologia = $this->especialidad('Psicología');
        $psicopedagogia = $this->especialidad('Psicopedagogía');
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($servicio, [
            ['especialidad_id' => $psicologia->id, 'cantidad_minima' => 1],
            ['especialidad_id' => $psicopedagogia->id, 'cantidad_minima' => 2],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Evaluación integral')
            ->assertJsonPath('data.dias_estimados', 2)
            ->assertJsonPath('data.especialidades_count', 2)
            ->assertJsonPath('can.tipos_diagnostico.create', true);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'tipo_diagnostico')
            ->where('accion', 'CREAR')
            ->sole();

        $this->assertCount(2, $audit->datos_nuevos['especialidades']);
    }

    public function test_duplicate_name_and_service_are_rejected(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación duplicada');
        $otroServicio = $this->servicioDiagnostico('Otra evaluación');
        $especialidad = $this->especialidad('Psicología duplicada');
        $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ], ['nombre' => 'Evaluación única']);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($otroServicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ], ['nombre' => '  evaluación   única ']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'TIPO_DIAGNOSTICO_NOMBRE_DUPLICADO');

        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ], ['nombre' => 'Otro nombre']))
            ->assertStatus(409)
            ->assertJsonPath('code', 'TIPO_DIAGNOSTICO_SERVICIO_DUPLICADO');
    }

    public function test_type_requires_at_least_one_specialty(): void
    {
        $servicio = $this->servicioDiagnostico('Sin especialidades');
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($servicio, []))
            ->assertStatus(422)
            ->assertJsonValidationErrors('especialidades');
    }

    public function test_service_must_be_diagnostic_type(): void
    {
        $servicio = $this->servicioNoDiagnostico('Entrevista no diagnóstica');
        $especialidad = $this->especialidad();
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'SERVICIO_NO_ES_DIAGNOSTICO');
    }

    public function test_inactive_service_and_inactive_specialty_are_rejected(): void
    {
        $servicio = $this->servicioDiagnostico('Servicio inactivo diagnóstico');
        $servicio->update(['activo' => false]);
        $especialidad = $this->especialidad('Especialidad inactiva');
        $especialidad->update(['activo' => false]);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]))
            ->assertStatus(409)
            ->assertJsonPath('code', 'SERVICIO_INACTIVO');

        $activeService = $this->servicioDiagnostico('Servicio activo diagnóstico');
        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', $this->tipoPayload($activeService, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'TIPO_DIAGNOSTICO_ESPECIALIDAD_INACTIVA');
    }

    public function test_update_syncs_specialties_and_audits_before_and_after(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación editable');
        $psicologia = $this->especialidad('Psicología editable');
        $psicopedagogia = $this->especialidad('Psicopedagogía editable');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $psicologia->id, 'cantidad_minima' => 1],
        ], ['nombre' => 'Evaluación editable']);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/tipos-diagnostico/{$tipo->id}", [
            'nombre' => 'Evaluación renombrada',
            'dias_estimados' => 3,
            'especialidades' => [
                ['especialidad_id' => $psicologia->id, 'cantidad_minima' => 2],
                ['especialidad_id' => $psicopedagogia->id, 'cantidad_minima' => 1],
            ],
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Evaluación renombrada')
            ->assertJsonPath('data.dias_estimados', 3)
            ->assertJsonPath('data.especialidades_count', 2)
            ->assertJsonPath('data.version', 2);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'tipo_diagnostico')
            ->where('accion', 'ACTUALIZAR')
            ->sole();

        $this->assertCount(1, $audit->datos_anteriores['especialidades']);
        $this->assertCount(2, $audit->datos_nuevos['especialidades']);
    }

    public function test_status_change_requires_reason_and_is_audited(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación estado');
        $especialidad = $this->especialidad('Psicología estado');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ], ['nombre' => 'Evaluación estado']);
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/tipos-diagnostico/{$tipo->id}/desactivar", [
            'version' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('motivo');

        $this->postJson("/api/catalogo-servicios/tipos-diagnostico/{$tipo->id}/desactivar", [
            'motivo' => 'Fin de evaluación',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tipo_diagnostico',
            'entidad_id' => $tipo->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Fin de evaluación',
        ]);
    }

    public function test_delete_is_always_rejected(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación sin borrado');
        $especialidad = $this->especialidad('Psicología sin borrado');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ], ['nombre' => 'Evaluación sin borrado']);
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/catalogo-servicios/tipos-diagnostico/{$tipo->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'TIPO_DIAGNOSTICO_DELETE_NOT_ALLOWED');
    }

    public function test_list_filters_by_state_and_specialty(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación filtro');
        $especialidad = $this->especialidad('Psicología filtro');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ], ['nombre' => 'Evaluación filtro']);
        $this->actingAsSuperadmin();

        $this->getJson(
            "/api/catalogo-servicios/tipos-diagnostico?estado=activo&especialidad_id={$especialidad->id}",
        )
            ->assertOk()
            ->assertJsonPath('data.0.id', $tipo->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_requirements_endpoint_and_contract_are_real(): void
    {
        $servicio = $this->servicioDiagnostico('Evaluación requisitos');
        $especialidad = $this->especialidad('Psicología requisitos');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 2],
        ], ['nombre' => 'Evaluación requisitos']);
        $this->actingAsSuperadmin();

        $this->getJson("/api/catalogo-servicios/tipos-diagnostico/{$tipo->id}/especialidades-requeridas")
            ->assertOk()
            ->assertJsonPath('data.0.especialidad_id', $especialidad->id)
            ->assertJsonPath('data.0.cantidad_minima', 2);

        $contract = $this->app->make(ProfessionalDiagnosticRequirementsContract::class);
        $this->assertSame([
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 2],
        ], $contract->especialidadesRequeridas($tipo->id));
    }

    public function test_administracion_and_recepcion_can_read_but_not_write(): void
    {
        $this->actingAsRole('administracion');

        $this->getJson('/api/catalogo-servicios/tipos-diagnostico')->assertOk();
        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', [])->assertForbidden();

        $this->actingAsRole('recepcion');
        $this->getJson('/api/catalogo-servicios/tipos-diagnostico')->assertOk();
        $this->postJson('/api/catalogo-servicios/tipos-diagnostico', [])->assertForbidden();
    }

    public function test_type_usage_stub_is_explicit(): void
    {
        $contract = $this->app->make(TipoDiagnosticoUsageContract::class);

        $this->expectException(LogicException::class);
        $contract->hasDiagnosticosRegistrados(1);
    }
}
