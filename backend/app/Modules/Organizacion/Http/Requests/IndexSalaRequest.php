<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado y filtros de salas (RF-008).
 */
final class IndexSalaRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'tipo_sala_id' => ['nullable', 'integer', 'exists:tipo_sala,id'],
            'turno_catalogo_id' => ['nullable', 'integer', 'exists:turno_catalogo,id'],
            'estado' => ['nullable', 'in:activa,inactiva'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:nombre,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
