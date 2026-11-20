<?php

namespace Database\Factories;

use App\Modules\Organizacion\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'color' => '#6B7280',
            'activo' => true,
        ];
    }
}
