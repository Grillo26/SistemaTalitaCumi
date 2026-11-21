<?php

namespace Database\Factories;

use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parametro>
 */
class ParametroFactory extends Factory
{
    protected $model = Parametro::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => 'prueba.'.fake()->unique()->slug(2),
            'categoria' => ParameterCategory::OPERACION->value,
            'descripcion' => fake()->sentence(6),
            'tipo' => ParameterType::TEXTO->value,
            'valor' => fake()->word(),
            'valor_defecto' => fake()->word(),
            'unidad' => null,
            'minimo' => null,
            'maximo' => null,
            'sensible' => false,
            'editable' => true,
            'visible_recepcion' => false,
            'version' => 1,
        ];
    }
}
