<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Asociación de tutor a paciente (04.02/RF-004).
 */
final class AssociateTutorRequest extends FormRequest
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
            'tutor_id' => ['required', 'integer', Rule::exists('tutor', 'id')],
            'parentesco_id' => [
                'nullable',
                'integer',
                Rule::exists('parentesco', 'id')->where('activo', true),
            ],
            'es_principal' => ['sometimes', 'boolean'],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tutor_id.required' => 'Seleccioná un tutor.',
            'tutor_id.exists' => 'El tutor seleccionado no existe.',
            'parentesco_id.exists' => 'El parentesco seleccionado no existe o está inactivo.',
        ];
    }
}
