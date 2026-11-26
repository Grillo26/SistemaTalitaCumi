<?php

namespace Tests\Feature\Modules\CatalogoServicios\Seeders;

use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class CatalogoTarifasSeederTest extends CatalogoServiciosTestCase
{
    public function test_general_category_exists_and_is_idempotent(): void
    {
        $this->assertDatabaseHas('categoria_tarifa', ['nombre' => 'General']);

        $before = CategoriaTarifa::query()->count();

        $this->seed(\Database\Seeders\CategoriaTarifaSeeder::class);

        $this->assertSame($before, CategoriaTarifa::query()->count());
    }

    public function test_no_tariffs_are_seeded(): void
    {
        $this->assertDatabaseCount('tarifa', 0);
    }

    public function test_permissions_are_seeded_for_roles(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'tarifas.ver']);
        $this->assertDatabaseHas('permissions', ['name' => 'tarifas.editar']);
        $this->assertDatabaseHas('permissions', ['name' => 'categorias.ver']);
        $this->assertDatabaseHas('permissions', ['name' => 'categorias.editar']);
    }
}
