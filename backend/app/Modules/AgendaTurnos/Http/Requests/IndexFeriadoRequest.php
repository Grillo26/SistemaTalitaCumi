<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Programación de Turnos] RF-016 / RN-008.
 */
final class IndexFeriadoRequest extends FormRequest
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
            'sucursal_id' => ['sometimes', 'nullable', 'integer', 'exists:sucursal,id'],
            'anio' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'activo' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
