<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Programación de Turnos] RF-016 / RN-008.
 */
final class StoreFeriadoRequest extends FormRequest
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
            'fecha' => ['required', 'date_format:Y-m-d'],
            'descripcion' => ['required', 'string', 'max:200'],
            'sucursal_id' => ['sometimes', 'nullable', 'integer', 'exists:sucursal,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha del feriado es obligatoria.',
            'fecha.date_format' => 'La fecha debe tener formato AAAA-MM-DD.',
            'descripcion.required' => 'La descripción del feriado es obligatoria.',
            'descripcion.max' => 'La descripción no puede superar los 200 caracteres.',
        ];
    }
}
