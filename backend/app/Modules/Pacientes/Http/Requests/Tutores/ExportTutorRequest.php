<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros de exportación de tutores (04.02/RF-016).
 */
final class ExportTutorRequest extends FormRequest
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
            'activo' => ['nullable', 'boolean'],
            'sucursal_registro_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'sort' => ['nullable', 'in:nombre_completo,ci,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
