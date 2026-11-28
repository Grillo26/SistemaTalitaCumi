<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 07.04/RF-014, 07.04/RN-010.
 */
final class ResumenDiarioRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
        ];
    }
}
