<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-001, RF-006, RF-007, RF-015.
 */
final class StoreEntrevistaRequest extends FormRequest
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
            'tutor_id' => ['required', 'integer', 'exists:tutor,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'servicio_id' => ['required', 'integer', 'exists:servicio,id'],
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'notas_admin' => ['nullable', 'string', 'max:500'],
            'tarifa_id_esperada' => ['nullable', 'integer'],
            'confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paciente_id.required' => 'El paciente es obligatorio.',
            'tutor_id.required' => 'El tutor es obligatorio.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'servicio_id.required' => 'El servicio de entrevista es obligatorio.',
            'categoria_tarifa_id.required' => 'La categoría de tarifa es obligatoria.',
            'fecha.required' => 'La fecha de la entrevista es obligatoria.',
            'notas_admin.max' => 'Las notas administrativas no pueden superar los 500 caracteres.',
            'monto_esperado.prohibited' => 'El monto lo calcula el sistema; no se acepta desde el cliente.',
        ];
    }
}
