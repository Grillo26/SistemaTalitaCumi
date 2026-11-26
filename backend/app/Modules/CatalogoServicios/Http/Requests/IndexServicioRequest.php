<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado y filtros de servicios (RF-C08).
 */
final class IndexServicioRequest extends FormRequest
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
            'area_id' => ['nullable', 'integer', 'exists:area,id'],
            'tipo_servicio_id' => ['nullable', 'integer', 'exists:tipo_servicio,id'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:nombre,area,tipo,duracion_minutos,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
