<?php

namespace Tests\Feature\Modules\CatalogoServicios\Tarifas;

use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaInactivaException;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Models\Tarifa;

final class TarifaApplicableTest extends TarifasTestCase
{
    public function test_branch_tariff_takes_precedence_over_global(): void
    {
        $servicio = $this->servicioActivo();
        $categoria = $this->categoria('Categoría Aplicable');
        $branch = $this->branch();
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => null,
            'monto' => '100.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branch->id,
            'monto' => '150.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->actingAsSuperadmin();

        $this->getJson(
            "/api/catalogo-servicios/tarifas/aplicable?servicio_id={$servicio->id}"
            ."&sucursal_id={$branch->id}&categoria_tarifa_id={$categoria->id}&fecha=2026-11-25",
        )
            ->assertOk()
            ->assertJsonPath('data.monto', '150.00')
            ->assertJsonPath('data.origen', 'sucursal')
            ->assertJsonPath('data.sucursal_id', $branch->id);
    }

    public function test_global_tariff_is_the_fallback_for_another_branch(): void
    {
        $servicio = $this->servicioActivo('Servicio Global Fallback');
        $categoria = $this->categoria('Categoría Global Fallback');
        $branchA = $this->branch();
        $branchB = $this->branch();
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => null,
            'monto' => '100.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branchA->id,
            'monto' => '150.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);
        $this->actingAsSuperadmin();

        $this->getJson(
            "/api/catalogo-servicios/tarifas/aplicable?servicio_id={$servicio->id}"
            ."&sucursal_id={$branchB->id}&categoria_tarifa_id={$categoria->id}&fecha=2026-11-25",
        )
            ->assertOk()
            ->assertJsonPath('data.monto', '100.00')
            ->assertJsonPath('data.origen', 'global')
            ->assertJsonPath('data.sucursal_id', null);
    }

    public function test_dates_are_inclusive_and_no_tariff_returns_null(): void
    {
        $servicio = $this->servicioActivo('Servicio Fechas Aplicable');
        $categoria = $this->categoria('Categoría Fechas Aplicable');
        $branch = $this->branch();
        $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branch->id,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-12-31',
        ]);
        $this->actingAsSuperadmin();

        $base = "/api/catalogo-servicios/tarifas/aplicable?servicio_id={$servicio->id}"
            ."&sucursal_id={$branch->id}&categoria_tarifa_id={$categoria->id}&fecha=";

        $this->getJson($base.'2026-01-01')->assertOk()->assertJsonPath('data.monto', '100.00');
        $this->getJson($base.'2026-12-31')->assertOk()->assertJsonPath('data.monto', '100.00');
        $this->getJson($base.'2027-01-01')->assertOk()->assertJsonPath('data', null);
    }

    public function test_inactive_service_or_category_is_rejected(): void
    {
        $servicio = $this->servicioActivo('Servicio Inactivo Aplicable');
        $servicio->update(['activo' => false]);
        $categoria = $this->categoria('Categoría Activa Aplicable');
        $branch = $this->branch();

        $this->expectException(ServicioInactivoException::class);
        $contract = $this->app->make(TarifaAplicableResolverContract::class);
        $contract->resolver($servicio->id, $branch->id, $categoria->id, '2026-11-25');
    }

    public function test_inactive_category_is_rejected_by_contract(): void
    {
        $servicio = $this->servicioActivo('Servicio Categoría Inactiva Aplicable');
        $categoria = $this->categoria('Categoría Inactiva Aplicable');
        $categoria->update(['activo' => false]);
        $branch = $this->branch();

        $this->expectException(CategoriaTarifaInactivaException::class);
        $contract = $this->app->make(TarifaAplicableResolverContract::class);
        $contract->resolver($servicio->id, $branch->id, $categoria->id, '2026-11-25');
    }

    public function test_contract_returns_dto_and_cache_is_invalidated_after_edit(): void
    {
        $servicio = $this->servicioActivo('Servicio Contrato Aplicable');
        $categoria = $this->categoria('Categoría Contrato Aplicable');
        $branch = $this->branch();
        $tarifa = $this->tarifa($servicio, $categoria, [
            'sucursal_id' => $branch->id,
            'monto' => '100.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);

        $contract = $this->app->make(TarifaAplicableResolverContract::class);
        $first = $contract->resolver($servicio->id, $branch->id, $categoria->id, '2026-11-25');
        $this->assertSame('100.00', $first?->monto);

        $this->actingAsSuperadmin();
        $this->patchJson("/api/catalogo-servicios/tarifas/{$tarifa->id}", [
            'monto' => '200.00',
            'version' => 1,
        ])->assertOk();

        $second = $contract->resolver($servicio->id, $branch->id, $categoria->id, '2026-11-25');
        $this->assertSame('200.00', $second?->monto);
    }
}
