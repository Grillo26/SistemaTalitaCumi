<?php

namespace App\Modules\Profesionales\Http\Requests\Directorio;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Asignación de una sucursal de trabajo (03/RF-E06 / 03/RN-07).
 */
final class StoreProfesionalSucursalRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
        ];
    }
}
