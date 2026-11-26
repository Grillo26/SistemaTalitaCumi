<?php

namespace Tests\Feature\Modules\CatalogoServicios\Tarifas;

use App\Models\User;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Support\Carbon;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;
use Tests\Support\FakeUserBranchAccessContract;

abstract class TarifasTestCase extends CatalogoServiciosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-25 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function categoria(string $name = 'Categoría de prueba'): CategoriaTarifa
    {
        return CategoriaTarifa::factory()->create([
            'nombre' => $name,
            'activo' => true,
            'version' => 1,
        ]);
    }

    protected function servicioActivo(string $name = 'Servicio de tarifa'): Servicio
    {
        return Servicio::factory()->create([
            'nombre' => $name,
            'activo' => true,
            'version' => 1,
        ]);
    }

    protected function branch(bool $active = true): Branch
    {
        return Branch::factory()->create(['activo' => $active]);
    }

    protected function fakeAccessFor(User $user, array $branchIds): FakeUserBranchAccessContract
    {
        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = $branchIds;
        $this->app->instance(UserBranchAccessContract::class, $access);

        return $access;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function tarifaPayload(
        Servicio $servicio,
        CategoriaTarifa $categoria,
        array $overrides = [],
    ): array {
        return array_merge([
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
            'sucursal_id' => null,
            'monto' => '150.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ], $overrides);
    }

    protected function tarifa(
        Servicio $servicio,
        CategoriaTarifa $categoria,
        array $overrides = [],
    ): Tarifa {
        return Tarifa::factory()->create(array_merge([
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
            'sucursal_id' => null,
            'monto' => '100.00',
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
            'activo' => true,
            'version' => 1,
        ], $overrides));
    }
}
