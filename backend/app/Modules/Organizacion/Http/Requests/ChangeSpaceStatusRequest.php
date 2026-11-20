<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado de tipos, turnos, salas y ambientes (RF-011 / RN-010).
 *
 * El motivo es obligatorio solo al desactivar; la Action de desactivación
 * lo valida. La activación no requiere motivo.
 */
final class ChangeSpaceStatusRequest extends FormRequest
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
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
