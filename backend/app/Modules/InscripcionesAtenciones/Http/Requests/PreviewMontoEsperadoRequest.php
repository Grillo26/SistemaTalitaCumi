<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Inscripción Salas Grupales] RF-005, RF-011, RN-011.
 */
final class PreviewMontoEsperadoRequest extends FormRequest
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
            'servicio_id' => ['required', 'integer', 'exists:servicio,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'tarifa_id_esperada' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'servicio_id.required' => 'El servicio es obligatorio.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'categoria_tarifa_id.required' => 'La categoría de tarifa es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener formato AAAA-MM-DD.',
        ];
    }
}
