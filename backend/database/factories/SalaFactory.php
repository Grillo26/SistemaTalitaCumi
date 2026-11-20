<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\Area;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sala>
 */
class SalaFactory extends Factory
{
    protected $model = Sala::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sucursal_id' => Branch::factory(),
            'area_id' => Area::factory(),
            'tipo_sala_id' => TipoSala::factory(),
            'turno_catalogo_id' => TurnoCatalogo::factory(),
            'nombre' => ucfirst(fake()->unique()->words(3, true)),
            'observaciones' => null,
            'activo' => true,
            'version' => 1,
        ];
    }
}
