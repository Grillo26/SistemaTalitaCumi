<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-008.
 */
final class IndexEntrevistaRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_desde', 'after_or_equal:periodo_desde'],
            'estado' => ['nullable', 'in:activa,retirada'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:fecha,monto_esperado,estado,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
