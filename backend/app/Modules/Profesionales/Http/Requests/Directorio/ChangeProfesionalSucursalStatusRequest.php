<?php

namespace App\Modules\Profesionales\Http\Requests\Directorio;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado de la relación profesional–sucursal (03/RN-20).
 */
final class ChangeProfesionalSucursalStatusRequest extends FormRequest
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
            'motivo.required' => 'El motivo es obligatorio para cambiar el estado de la relación con la sucursal.',
            'version.required' => 'La versión de la relación es obligatoria.',
        ];
    }
}
