<?php

namespace Tests\Feature\Modules\CatalogoServicios\Areas;

use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class AreaTest extends CatalogoServiciosTestCase
{
    public function test_superadmin_creates_area_and_audits(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/areas', $this->areaPayload([
            'nombre' => '  Intervención   Temprana ',
            'color' => '#671c61',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Intervención Temprana')
            ->assertJsonPath('data.color', '#671C61')
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('can.areas.create', true);

        $audit = BitacoraEntry::query()->where('entidad', 'area')->sole();

        $this->assertSame('CREAR', $audit->accion);
        $this->assertSame('Intervención Temprana', $audit->datos_nuevos['nombre']);
    }

    public function test_duplicate_area_name_is_rejected_case_and_space_insensitive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/areas', $this->areaPayload([
            'nombre' => '  salas   grupales ',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'AREA_NOMBRE_DUPLICADO');
    }

    public function test_area_update_and_color_change_are_audited(): void
    {
        $area = $this->area('Área Editable');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/areas/{$area->id}", [
            'nombre' => 'Área Renombrada',
            'color' => '#F9733F',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Área Renombrada')
            ->assertJsonPath('data.color', '#F9733F')
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'area',
            'entidad_id' => $area->id,
            'accion' => 'ACTUALIZAR',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'area',
            'entidad_id' => $area->id,
            'accion' => 'CAMBIAR_COLOR',
        ]);
    }

    public function test_stale_version_returns_conflict(): void
    {
        $area = $this->area('Área Versión');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/areas/{$area->id}", [
            'nombre' => 'Otro nombre',
            'version' => 99,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'VERSION_CONFLICT');
    }

    public function test_area_can_be_deactivated_and_activated_with_reason(): void
    {
        $area = $this->area('Área Estado');
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/areas/{$area->id}/desactivar", [
            'motivo' => 'Cierre temporal',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'area',
            'entidad_id' => $area->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Cierre temporal',
        ]);

        $this->postJson("/api/catalogo-servicios/areas/{$area->id}/activar", [
            'motivo' => 'Reapertura',
            'version' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 3);
    }

    public function test_area_with_active_services_cannot_be_deactivated(): void
    {
        $area = $this->area('Área Con Servicios');
        $tipo = $this->tipoServicio();
        $this->servicio($area, $tipo);

        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/areas/{$area->id}/desactivar", [
            'motivo' => 'Cierre',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'AREA_CON_SERVICIOS_ACTIVOS')
            ->assertJsonPath('active_services_count', 1);
    }

    public function test_status_requires_reason(): void
    {
        $area = $this->area('Área Motivo');
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/areas/{$area->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_delete_is_always_rejected(): void
    {
        $area = $this->area('Área Sin Borrado');
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/catalogo-servicios/areas/{$area->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'AREA_DELETE_NOT_ALLOWED');
    }

    public function test_list_filters_by_search_and_state(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/catalogo-servicios/areas?search=Grupales&estado=activo')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Salas Grupales')
            ->assertJsonPath('data.0.servicios_activos_count', 0);
    }

    public function test_administracion_and_recepcion_can_read_but_not_write(): void
    {
        foreach (['administracion', 'recepcion'] as $role) {
            $this->actingAsRole($role);

            $this->getJson('/api/catalogo-servicios/areas')->assertOk();
            $this->postJson('/api/catalogo-servicios/areas', $this->areaPayload())
                ->assertForbidden();
        }
    }
}
