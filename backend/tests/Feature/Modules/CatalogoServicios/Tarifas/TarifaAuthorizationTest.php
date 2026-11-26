<?php

namespace Tests\Feature\Modules\CatalogoServicios\Tarifas;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;

final class TarifaAuthorizationTest extends TarifasTestCase
{
    public function test_administracion_can_edit_own_branch_but_not_global_or_other_branch(): void
    {
        $servicio = $this->servicioActivo('Servicio Permisos Tarifa');
        $categoria = $this->categoria('Categoría Permisos Tarifa');
        $branchA = $this->branch();
        $branchB = $this->branch();
        $admin = $this->actingAsRole('administracion');
        $this->fakeAccessFor($admin, [$branchA->id]);

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'sucursal_id' => $branchA->id,
            'vigente_desde' => '2027-01-01',
        ]))->assertCreated();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'sucursal_id' => $branchB->id,
            'vigente_desde' => '2027-02-01',
        ]))->assertForbidden();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'sucursal_id' => null,
            'vigente_desde' => '2027-03-01',
        ]))->assertForbidden();

        $branchATariff = Tarifa::query()
            ->where('servicio_id', $servicio->id)
            ->where('sucursal_id', $branchA->id)
            ->sole();

        $this->patchJson("/api/catalogo-servicios/tarifas/{$branchATariff->id}", [
            'monto' => '175.00',
            'version' => 1,
        ])->assertOk();
    }

    public function test_administracion_cannot_edit_global_tariff(): void
    {
        $servicio = $this->servicioActivo('Servicio Global Permisos');
        $categoria = $this->categoria('Categoría Global Permisos');
        $branch = $this->branch();
        $global = $this->tarifa($servicio, $categoria, [
            'sucursal_id' => null,
            'vigente_desde' => '2027-01-01',
        ]);
        $admin = $this->actingAsRole('administracion');
        $this->fakeAccessFor($admin, [$branch->id]);

        $this->patchJson("/api/catalogo-servicios/tarifas/{$global->id}", [
            'monto' => '175.00',
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_administracion_list_includes_global_and_own_branch_only(): void
    {
        $servicio = $this->servicioActivo('Servicio Lista Permisos');
        $categoria = $this->categoria('Categoría Lista Permisos');
        $branchA = $this->branch();
        $branchB = $this->branch();
        $global = $this->tarifa($servicio, $categoria, [
            'sucursal_id' => null,
            'vigente_desde' => '2027-01-01',
        ]);
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branchB->id,
            'vigente_desde' => '2027-02-01',
        ]);
        $admin = $this->actingAsRole('administracion');
        $this->fakeAccessFor($admin, [$branchA->id]);

        $response = $this->getJson('/api/catalogo-servicios/tarifas')->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($global->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_recepcion_can_read_but_not_write(): void
    {
        $servicio = $this->servicioActivo('Servicio Recepción Tarifa');
        $categoria = $this->categoria('Categoría Recepción Tarifa');
        $branch = $this->branch();
        $reception = $this->actingAsRole('recepcion');
        $this->fakeAccessFor($reception, [$branch->id]);

        $this->getJson('/api/catalogo-servicios/tarifas')->assertOk();
        $this->getJson(
            "/api/catalogo-servicios/tarifas/aplicable?servicio_id={$servicio->id}"
            ."&sucursal_id={$branch->id}&categoria_tarifa_id={$categoria->id}&fecha=2026-11-25",
        )->assertOk();

        $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'sucursal_id' => $branch->id,
        ]))->assertForbidden();
    }

    public function test_recepcion_cannot_query_another_branch(): void
    {
        $servicio = $this->servicioActivo('Servicio Rama Recepción');
        $categoria = $this->categoria('Categoría Rama Recepción');
        $branchA = $this->branch();
        $branchB = $this->branch();
        $reception = $this->actingAsRole('recepcion');
        $this->fakeAccessFor($reception, [$branchA->id]);

        $this->getJson(
            "/api/catalogo-servicios/tarifas/aplicable?servicio_id={$servicio->id}"
            ."&sucursal_id={$branchB->id}&categoria_tarifa_id={$categoria->id}&fecha=2026-11-25",
        )->assertForbidden();
    }
}
