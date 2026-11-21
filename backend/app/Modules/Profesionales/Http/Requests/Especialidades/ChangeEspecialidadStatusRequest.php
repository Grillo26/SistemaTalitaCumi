<?php

namespace App\Modules\Profesionales\Http\Requests\Especialidades;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado de especialidad (03/RN-20).
 */
final class ChangeEspecialidadStatusRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'max:1000'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo es obligatorio para cambiar el estado de la especialidad.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
