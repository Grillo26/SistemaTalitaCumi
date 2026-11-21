<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de tutor (04.02/RF-001, 04.02/RF-002).
 */
final class StoreTutorRequest extends FormRequest
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
            'nombre_completo' => ['required', 'string', 'max:200'],
            'ci' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'telefono' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'telefono_alternativo' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'parentesco' => ['required', 'string', 'max:50'],
            'activo' => ['prohibited'],
            'version' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_completo.required' => 'El nombre completo del tutor es obligatorio.',
            'telefono.required' => 'El teléfono principal es obligatorio.',
            'telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'parentesco.required' => 'El parentesco es obligatorio.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
        ];
    }
}
