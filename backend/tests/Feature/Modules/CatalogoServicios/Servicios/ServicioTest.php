<?php

namespace Tests\Feature\Modules\CatalogoServicios\Servicios;

use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Models\Servicio;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class ServicioTest extends CatalogoServiciosTestCase
{
    public function test_superadmin_creates_service_and_audits(): void
    {
        $area = $this->area('Intervención Terapéutica Test');
        $tipo = $this->tipoServicio('Terapia individual Test');
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/servicios', $this->servicioPayload($area, $tipo, [
            'nombre' => '  Terapia   de lenguaje ',
            'duracion_minutos' => 45,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Terapia de lenguaje')
            ->assertJsonPath('data.duracion_minutos', 45)
            ->assertJsonPath('data.area.id', $area->id)
            ->assertJsonPath('data.tipo_servicio.id', $tipo->id)
            ->assertJsonPath('data.version', 1);

        $audit = BitacoraEntry::query()->where('entidad', 'servicio')->sole();
        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame('Terapia de lenguaje', $audit->datos_nuevos['nombre']);
    }

    public function test_same_service_name_is_allowed_in_another_area(): void
    {
        $areaA = $this->area('Área A');
        $areaB = $this->area('Área B');
        $tipo = $this->tipoServicio();
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/servicios', $this->servicioPayload($areaA, $tipo, [
            'nombre' => 'Terapia compartida',
        ]))->assertCreated();

        $this->postJson('/api/catalogo-servicios/servicios', $this->servicioPayload($areaB, $tipo, [
            'nombre' => 'Terapia compartida',
        ]))->assertCreated();
    }

    public function test_duplicate_service_name_is_rejected_within_same_area(): void
    {
        $area = $this->area('Área Duplicada');
        $tipo = $this->tipoServicio();
        $this->servicio($area, $tipo, 'Terapia de lenguaje');
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/servicios', $this->servicioPayload($area, $tipo, [
            'nombre' => '  terapia   de lenguaje ',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'SERVICIO_NOMBRE_DUPLICADO');
    }

    public function test_service_update_and_show_return_last_modification(): void
    {
        $area = $this->area('Área Editable Servicio');
        $tipo = $this->tipoServicio();
        $servicio = $this->servicio($area, $tipo, 'Servicio Editable');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/servicios/{$servicio->id}", [
            'nombre' => 'Servicio Renombrado',
            'duracion_minutos' => 60,
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Servicio Renombrado')
            ->assertJsonPath('data.duracion_minutos', 60)
            ->assertJsonPath('data.version', 2);

        $this->getJson("/api/catalogo-servicios/servicios/{$servicio->id}")
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Servicio Renombrado')
            ->assertJsonStructure(['data' => ['updated_at', 'area', 'tipo_servicio']]);
    }

    public function test_service_status_requires_reason_and_is_audited(): void
    {
        $area = $this->area('Área Estado Servicio');
        $tipo = $this->tipoServicio();
        $servicio = $this->servicio($area, $tipo);
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/servicios/{$servicio->id}/desactivar", [
            'version' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('motivo');

        $this->postJson("/api/catalogo-servicios/servicios/{$servicio->id}/desactivar", [
            'motivo' => 'Fin de ciclo',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'servicio',
            'entidad_id' => $servicio->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Fin de ciclo',
        ]);
    }

    public function test_service_cannot_be_activated_when_area_is_inactive(): void
    {
        $area = $this->area('Área Inactiva Servicio');
        $tipo = $this->tipoServicio();
        $servicio = $this->servicio($area, $tipo, 'Servicio Inactivo');
        $servicio->update(['activo' => false, 'version' => 1]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/areas/{$area->id}/desactivar", [
            'motivo' => 'Cierre del área',
            'version' => 1,
        ])->assertOk();

        $this->postJson("/api/catalogo-servicios/servicios/{$servicio->id}/activar", [
            'motivo' => 'Reapertura del servicio',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'AREA_INACTIVA');
    }

    public function test_delete_is_always_rejected(): void
    {
        $area = $this->area('Área Borrado Servicio');
        $tipo = $this->tipoServicio();
        $servicio = $this->servicio($area, $tipo);
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/catalogo-servicios/servicios/{$servicio->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'SERVICIO_DELETE_NOT_ALLOWED');
    }

    public function test_list_filters_by_area_type_state_and_search(): void
    {
        $area = $this->area('Área Filtro');
        $tipo = $this->tipoServicio('Tipo Filtro');
        $servicio = $this->servicio($area, $tipo, 'Servicio Filtrable');
        $this->actingAsSuperadmin();

        $this->getJson(
            "/api/catalogo-servicios/servicios?search=Filtrable&area_id={$area->id}"
            ."&tipo_servicio_id={$tipo->id}&estado=activo",
        )
            ->assertOk()
            ->assertJsonPath('data.0.id', $servicio->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_administracion_and_recepcion_can_read_but_not_write(): void
    {
        $area = $this->area('Área Permisos Servicio');
        $tipo = $this->tipoServicio();

        foreach (['administracion', 'recepcion'] as $role) {
            $this->actingAsRole($role);

            $this->getJson('/api/catalogo-servicios/servicios')->assertOk();
            $this->postJson(
                '/api/catalogo-servicios/servicios',
                $this->servicioPayload($area, $tipo),
            )->assertForbidden();
        }
    }
}
