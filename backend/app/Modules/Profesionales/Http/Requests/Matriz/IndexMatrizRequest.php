<?php

namespace App\Modules\Profesionales\Http\Requests\Matriz;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros de la matriz profesional–especialidad–sucursal (03/RF-E08).
 */
final class IndexMatrizRequest extends FormRequest
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
            'profesional_id' => ['nullable', 'integer', 'exists:profesional,id'],
            'especialidad_id' => ['nullable', 'integer', 'exists:especialidad,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'solo_asignables' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:profesional_nombre,profesional_ci,especialidad_nombre,sucursal_nombre,profesional_updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
