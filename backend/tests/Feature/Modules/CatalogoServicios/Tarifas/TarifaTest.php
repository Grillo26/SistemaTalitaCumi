<?php

namespace Tests\Feature\Modules\CatalogoServicios\Tarifas;

use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;

final class TarifaTest extends TarifasTestCase
{
    public function test_superadmin_creates_global_tariff_and_audits(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('General Test');
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'monto' => '150',
            'vigente_desde' => '2026-01-01',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.monto', '150.00')
            ->assertJsonPath('data.sucursal', null)
            ->assertJsonPath('data.estado_vigencia', 'vigente')
            ->assertJsonPath('data.can.update', true);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tarifa',
            'accion' => 'CREAR',
        ]);
    }

    public function test_overlapping_tariff_is_rejected_with_conflict(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Solape');
        $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-12-31',
        ]);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'vigente_desde' => '2026-06-01',
            'vigente_hasta' => '2027-03-31',
        ]))
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_SOLAPADA')
            ->assertJsonPath('conflict.id', fn ($id) => is_int($id));
    }

    public function test_contiguous_tariffs_are_allowed(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Contigua');
        $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-06-30',
        ]);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'vigente_desde' => '2026-07-01',
            'vigente_hasta' => null,
        ]))->assertCreated();
    }

    public function test_global_and_branch_tariffs_can_coexist(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Global Rama');
        $branch = $this->branch();
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => null,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'sucursal_id' => $branch->id,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]))->assertCreated();
    }

    public function test_same_period_in_another_branch_is_allowed(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Otra Rama');
        $branchA = $this->branch();
        $branchB = $this->branch();
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branchA->id,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'sucursal_id' => $branchB->id,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]))->assertCreated();
    }

    public function test_edit_excludes_own_tariff_from_overlap(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Edición');
        $tarifa = $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => '2027-12-31',
        ]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/tarifas/{$tarifa->id}", [
            'monto' => '200.00',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.monto', '200.00')
            ->assertJsonPath('data.version', 2);
    }

    public function test_edit_into_another_tariff_scope_is_rejected(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Edición Solape');
        $branchA = $this->branch();
        $branchB = $this->branch();
        $tarifaA = $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branchA->id,
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => '2027-12-31',
        ]);
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branchB->id,
            'vigente_desde' => '2027-06-01',
            'vigente_hasta' => '2027-06-30',
        ]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/tarifas/{$tarifaA->id}", [
            'sucursal_id' => $branchB->id,
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_SOLAPADA');
    }

    public function test_closed_tariff_cannot_be_edited(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Cerrada Edición');
        $tarifa = $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-06-30',
        ]);
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/tarifas/{$tarifa->id}", [
            'monto' => '200.00',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_CERRADA');
    }

    public function test_close_vigencia_requires_reason_and_audits(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Cierre');
        $tarifa = $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/tarifas/{$tarifa->id}/cerrar-vigencia", [
            'vigente_hasta' => '2026-12-31',
            'version' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('motivo');

        $this->postJson("/api/catalogo-servicios/tarifas/{$tarifa->id}/cerrar-vigencia", [
            'vigente_hasta' => '2026-12-31',
            'motivo' => 'Fin de año',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.vigente_hasta', '2026-12-31')
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tarifa',
            'entidad_id' => $tarifa->id,
            'accion' => 'CERRAR_VIGENCIA',
            'motivo' => 'Fin de año',
        ]);
    }

    public function test_already_closed_tariff_cannot_be_closed_again(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Ya Cerrada');
        $tarifa = $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-12-31',
        ]);
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/tarifas/{$tarifa->id}/cerrar-vigencia", [
            'vigente_hasta' => '2026-12-31',
            'motivo' => 'Repetido',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_YA_CERRADA');
    }

    public function test_inactive_service_and_category_are_rejected_on_create(): void
    {
        $servicio = $this->servicioActivo();
        $servicio->update(['activo' => false]);
        $categoria = $this->categoria('Categoría Inactiva');
        $categoria->update(['activo' => false]);
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria))
            ->assertStatus(409)
            ->assertJsonPath('code', 'SERVICIO_INACTIVO');

        $activeService = $this->servicioActivo('Servicio Activo Categoría Inactiva');
        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($activeService, $categoria))
            ->assertStatus(409)
            ->assertJsonPath('code', 'CATEGORIA_TARIFA_INACTIVA');
    }

    public function test_delete_is_always_rejected(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Sin Borrado Tarifa');
        $tarifa = $this->tarifa($servicio, $categoria);
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/catalogo-servicios/tarifas/{$tarifa->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_DELETE_NOT_ALLOWED');
    }

    public function test_list_filters_by_state_and_service(): void
    {
        $servicio = $this->servicioActivo('Servicio Filtro Tarifa');
        $categoria = $this->categoria('Categoría Filtro Tarifa');
        $futura = $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => null,
        ]);
        $this->actingAsSuperadmin();

        $this->getJson(
            "/api/catalogo-servicios/tarifas?servicio_id={$servicio->id}&estado_vigencia=futura",
        )
            ->assertOk()
            ->assertJsonPath('data.0.id', $futura->id)
            ->assertJsonPath('meta.total', 1);
    }
}
