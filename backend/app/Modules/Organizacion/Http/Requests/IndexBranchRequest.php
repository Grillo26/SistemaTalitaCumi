<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado y búsqueda de sucursales.
 *
 * RF-004 / RF-005.
 */
final class IndexBranchRequest extends FormRequest
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
            'estado' => ['nullable', 'in:activa,inactiva'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:codigo,nombre,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
