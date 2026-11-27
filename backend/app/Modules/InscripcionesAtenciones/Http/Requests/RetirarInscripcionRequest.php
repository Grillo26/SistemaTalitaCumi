<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-004 / RF-005 / CU-03 / CA-007.
 */
final class RetirarInscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hoy = VigenciaPeriod::today();
        $limite = VigenciaPeriod::addDays($hoy, -30);

        return [
            'estado_esperado' => ['required', Rule::enum(EstadoInscripcion::class)],
            'motivo' => ['required', 'string', 'max:50'],
            'motivo_detalle' => ['nullable', 'string', 'max:500'],
            'fecha_baja' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$limite, 'before_or_equal:'.$hoy],
        ];
    }
}
