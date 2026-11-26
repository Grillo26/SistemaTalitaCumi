<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado de tipos de diagnóstico (RF-D06).
 */
final class IndexTipoDiagnosticoRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'especialidad_id' => ['nullable', 'integer', 'exists:especialidad,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:nombre,activo,dias_estimados,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
