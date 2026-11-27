<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Asistencia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 07.04/RF-009, 07.04/CU-03.
 */
class IndicadoresAsistenciaRequest extends FormRequest
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
            'periodo_desde' => ['required', 'date_format:Y-m-d'],
            'periodo_hasta' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:periodo_desde',
            ],
            'agrupacion' => ['required', Rule::in([
                'paciente',
                'sala',
                'servicio',
                'profesional',
                'sucursal',
                'dia',
            ])],
            'sala_id' => ['nullable', 'integer', 'exists:sala,id'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicio,id'],
            'profesional_id' => ['nullable', 'integer', 'exists:profesional,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
