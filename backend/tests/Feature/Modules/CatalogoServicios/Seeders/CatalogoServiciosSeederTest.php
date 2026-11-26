<?php

namespace Tests\Feature\Modules\CatalogoServicios\Seeders;

use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class CatalogoServiciosSeederTest extends CatalogoServiciosTestCase
{
    public function test_area_seeder_creates_two_base_areas_with_token_colors(): void
    {
        $terapeutica = Area::query()->where('nombre', 'Intervención Terapéutica')->sole();
        $grupales = Area::query()->where('nombre', 'Salas Grupales')->sole();

        $this->assertSame('#671C61', $terapeutica->color);
        $this->assertSame('#F9733F', $grupales->color);
    }

    public function test_area_seeder_is_idempotent(): void
    {
        $before = Area::query()->count();

        $this->seed(\Database\Seeders\AreaSeeder::class);

        $this->assertSame($before, Area::query()->count());
    }

    public function test_type_seeder_loads_four_types_and_no_services(): void
    {
        $this->assertSame(4, TipoServicio::query()->count());
        $this->assertDatabaseCount('servicio', 0);
    }
}
