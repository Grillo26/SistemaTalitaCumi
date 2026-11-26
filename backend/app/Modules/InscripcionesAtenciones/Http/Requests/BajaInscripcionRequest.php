<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBaja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Inscripción Salas Grupales] RF-011, RN-009.
 */
final class BajaInscripcionRequest extends FormRequest
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
        return [
            'motivo' => ['required', Rule::enum(MotivoBaja::class)],
            'motivo_detalle' => ['nullable', 'string', 'max:500'],
            'fecha_baja' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de la baja es obligatorio.',
            'fecha_baja.required' => 'La fecha de baja es obligatoria.',
            'fecha_baja.date_format' => 'La fecha de baja debe tener formato AAAA-MM-DD.',
            'fecha_baja.before_or_equal' => 'La fecha de baja no puede ser futura.',
        ];
    }
}
