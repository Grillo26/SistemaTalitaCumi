<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Reprogramación y Cancelación] RF-012 / RNF-010.
 */
final class ExportarCambiosRequest extends FormRequest
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
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:periodo_desde',
                'after_or_equal:periodo_desde',
            ],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'tipo_cambio' => ['nullable', Rule::in(['reprogramado', 'cancelado'])],
            'motivo_id' => ['nullable', 'integer', 'exists:motivo_turno,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicio,id'],
        ];
    }
}
