<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login por correo o nombre de usuario (02/RF-A01).
 */
final class LoginRequest extends FormRequest
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
            'identificador' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
            'recordar' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identificador.required' => 'Ingresá tu correo o nombre de usuario.',
            'identificador.max' => 'El identificador no puede superar los 150 caracteres.',
            'password.required' => 'Ingresá tu contraseña.',
            'password.max' => 'La contraseña no puede superar los 255 caracteres.',
        ];
    }
}
