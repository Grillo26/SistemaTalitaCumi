<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado del tutor (04.02/RF-010).
 */
final class ChangeTutorStatusRequest extends FormRequest
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
            'activo' => ['required', 'boolean'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'activo.required' => 'Indicá si el tutor queda activo o inactivo.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
