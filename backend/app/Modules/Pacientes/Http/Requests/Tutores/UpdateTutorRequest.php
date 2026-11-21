<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de tutor (04.02/RF-009).
 */
final class UpdateTutorRequest extends FormRequest
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
            'nombre_completo' => ['sometimes', 'string', 'max:200'],
            'ci' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'telefono' => ['sometimes', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'telefono_alternativo' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'parentesco' => ['sometimes', 'string', 'max:50'],
            'version' => ['required', 'integer', 'min:1'],
            'activo' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
