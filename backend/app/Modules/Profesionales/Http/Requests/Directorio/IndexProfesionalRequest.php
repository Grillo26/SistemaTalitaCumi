<?php

namespace App\Modules\Profesionales\Http\Requests\Directorio;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del directorio (03/RF-D04).
 */
final class IndexProfesionalRequest extends FormRequest
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
            'especialidad_id' => ['nullable', 'integer', 'exists:especialidad,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:nombre_completo,ci,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
