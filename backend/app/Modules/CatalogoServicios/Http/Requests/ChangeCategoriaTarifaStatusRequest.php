<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado de categoría de tarifa (RF-T02 / RN-T12).
 *
 * El motivo es obligatorio solo al desactivar; la Action lo valida.
 */
final class ChangeCategoriaTarifaStatusRequest extends FormRequest
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
