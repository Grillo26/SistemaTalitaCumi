<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Inscripción Salas Grupales] RF-001, RF-006, RN-001, RN-004, RN-008.
 */
final class StoreInscripcionRequest extends FormRequest
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
            'paciente_id' => ['required', 'integer', 'exists:paciente,id'],
            'tutor_responsable_id' => ['required', 'integer', 'exists:tutor,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'sala_id' => ['required', 'integer', 'exists:sala,id'],
            'servicio_id' => ['required', 'integer', 'exists:servicio,id'],
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'tarifa_id_esperada' => ['nullable', 'integer'],
            'confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
            'inscripcion_origen_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paciente_id.required' => 'El paciente es obligatorio.',
            'tutor_responsable_id.required' => 'El tutor responsable es obligatorio.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sala_id.required' => 'La sala es obligatoria.',
            'servicio_id.required' => 'El servicio es obligatorio.',
            'categoria_tarifa_id.required' => 'La categoría de tarifa es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior al inicio.',
            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres.',
            'monto_esperado.prohibited' => 'El monto lo calcula el sistema; no se acepta desde el cliente.',
        ];
    }
}
