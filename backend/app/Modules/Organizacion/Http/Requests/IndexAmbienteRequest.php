<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Enums\UsoAmbiente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Listado y filtros de ambientes (RF-008).
 */
final class IndexAmbienteRequest extends FormRequest
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
            'uso' => ['nullable', Rule::in(UsoAmbiente::values())],
            'estado' => ['nullable', 'in:activa,inactiva'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:nombre,uso,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
