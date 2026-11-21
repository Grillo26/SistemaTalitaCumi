<?php

namespace Database\Factories;

use App\Modules\Profesionales\Models\Especialidad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Especialidad>
 */
class EspecialidadFactory extends Factory
{
    protected $model = Especialidad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->unique()->words(2, true).' '.Str::upper(Str::random(4));

        return [
            'nombre' => $nombre,
            'descripcion' => fake()->sentence(),
            'activo' => true,
            'version' => 1,
        ];
    }
}
