<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edición de usuario, rol y sucursales (02/RF-U08).
 */
final class UpdateUserRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'documento' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]{4,20}$/'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
            'username' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]{3,100}$/'],
            'rol_id' => ['sometimes', 'required', 'integer', Rule::exists('roles', 'id')],
            'sucursales' => ['sometimes', 'required', 'array', 'min:1'],
            'sucursales.*' => ['integer', Rule::exists('sucursal', 'id')],
            'version' => ['required', 'integer', 'min:1'],
            'activo' => ['prohibited'],
            'password' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (['name', 'documento', 'email', 'username', 'rol_id', 'sucursales'] as $field) {
                    if ($this->exists($field)) {
                        return;
                    }
                }

                $validator->errors()->add('name', 'Enviá al menos un campo para actualizar.');
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre no puede quedar vacío.',
            'documento.required' => 'El documento no puede quedar vacío.',
            'username.required' => 'El nombre de usuario no puede quedar vacío.',
            'rol_id.required' => 'Seleccioná un rol.',
            'sucursales.required' => 'Asigná al menos una sucursal.',
            'sucursales.min' => 'Asigná al menos una sucursal.',
            'version.required' => 'La versión del usuario es obligatoria.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
            'password.prohibited' => 'La contraseña se cambia desde la acción de reseteo o el cambio propio.',
        ];
    }
}
