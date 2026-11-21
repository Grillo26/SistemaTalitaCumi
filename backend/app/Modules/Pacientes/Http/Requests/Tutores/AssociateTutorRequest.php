<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Asociación de tutor a paciente (04.01/RF-010).
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
            'es_principal' => ['sometimes', 'boolean'],
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
        ];
    }
}
