<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del listado de tutores (04.02/RF-007).
 */
final class IndexTutorRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:nombre_completo,ci,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
