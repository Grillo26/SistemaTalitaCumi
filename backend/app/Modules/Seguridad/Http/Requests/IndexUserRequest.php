<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Listado de usuarios (02/RF-U06).
 */
final class IndexUserRequest extends FormRequest
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
            'rol_id' => ['nullable', 'integer', 'exists:roles,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo', 'bloqueado'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', Rule::in(['nombre', 'email', 'activo', 'ultimo_acceso', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
