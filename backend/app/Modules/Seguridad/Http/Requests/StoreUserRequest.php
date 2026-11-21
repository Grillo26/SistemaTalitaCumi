<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de usuario (02/RF-U01–U05 / 02/RN-01, 02/RN-02, 02/RN-03).
 */
final class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'documento' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]{4,20}$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'username' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]{3,100}$/'],
            'rol_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'sucursales' => ['required', 'array', 'min:1'],
            'sucursales.*' => ['integer', Rule::exists('sucursal', 'id')],
            'activo' => ['prohibited'],
            'password' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre completo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 200 caracteres.',
            'documento.required' => 'El documento de identidad es obligatorio.',
            'documento.regex' => 'El documento debe tener 4–20 caracteres alfanuméricos o guiones.',
            'email.email' => 'El correo no tiene un formato válido.',
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.regex' => 'El usuario debe tener 3–100 caracteres: letras, números, punto, guion o guion bajo.',
            'rol_id.required' => 'Seleccioná un rol.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
            'sucursales.required' => 'Asigná al menos una sucursal.',
            'sucursales.min' => 'Asigná al menos una sucursal.',
            'sucursales.*.exists' => 'Una de las sucursales seleccionadas no existe.',
            'activo.prohibited' => 'El usuario se crea activo; el estado se cambia con las acciones correspondientes.',
            'password.prohibited' => 'La contraseña se genera automáticamente y se muestra una sola vez.',
        ];
    }
}
