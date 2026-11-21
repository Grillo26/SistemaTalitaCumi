<?php

namespace Database\Factories;

use App\Modules\Pacientes\Models\ConsentimientoTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsentimientoTipo>
 */
class ConsentimientoTipoFactory extends Factory
{
    protected $model = ConsentimientoTipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $codigo = 'tipo_'.fake()->unique()->numberBetween(1, 999999);

        return [
            'codigo' => $codigo,
            'nombre' => 'Tipo de consentimiento '.$codigo,
            'descripcion' => null,
            'orden' => fake()->numberBetween(0, 100),
            'activo' => true,
            'version' => 1,
        ];
    }
}
