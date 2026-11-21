<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de la matriz de un rol (02/RF-R04, 02/RF-R13).
 */
final class UpdateRolePermissionsRequest extends FormRequest
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
            'permisos' => ['present', 'array'],
            'permisos.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permisos.present' => 'Enviá la lista de permisos del rol (puede estar vacía).',
            'permisos.array' => 'Los permisos deben enviarse como lista.',
            'permisos.*.integer' => 'Cada permiso debe ser un identificador válido.',
            'permisos.*.distinct' => 'No se puede repetir un permiso.',
            'permisos.*.exists' => 'Uno de los permisos seleccionados no existe.',
            'version.required' => 'La versión de la matriz es obligatoria.',
            'version.min' => 'La versión de la matriz no es válida.',
        ];
    }
}
