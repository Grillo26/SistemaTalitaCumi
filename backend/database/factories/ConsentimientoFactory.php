<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Pacientes\Enums\ResultadoConsentimiento;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\ConsentimientoTipo;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consentimiento>
 */
class ConsentimientoFactory extends Factory
{
    protected $model = Consentimiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ficha_ingreso_id' => FichaIngreso::factory(),
            'consentimiento_tipo_id' => ConsentimientoTipo::factory(),
            'tutor_id' => Tutor::factory(),
            'resultado' => ResultadoConsentimiento::OTORGADO->value,
            'fecha' => now()->toDateString(),
            'motivo' => null,
            'usuario_id' => User::factory(),
            'revoca_a_id' => null,
            'created_at' => now(),
        ];
    }
}
