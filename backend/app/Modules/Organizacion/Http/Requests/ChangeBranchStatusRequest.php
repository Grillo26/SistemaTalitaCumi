<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado de sucursal.
 *
 * RF-007 / RN-005: el motivo es obligatorio en ambos sentidos.
 */
final class ChangeBranchStatusRequest extends FormRequest
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
            'motivo.required' => 'El motivo es obligatorio para cambiar el estado de la sucursal.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
            'version.required' => 'La versión de la sucursal es obligatoria.',
        ];
    }
}
