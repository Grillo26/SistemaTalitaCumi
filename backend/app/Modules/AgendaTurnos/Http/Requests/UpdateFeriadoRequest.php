<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Programación de Turnos] RF-016.
 */
final class UpdateFeriadoRequest extends FormRequest
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
            'fecha' => ['sometimes', 'date_format:Y-m-d'],
            'descripcion' => ['sometimes', 'string', 'max:200'],
            'sucursal_id' => ['sometimes', 'nullable', 'integer', 'exists:sucursal,id'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
