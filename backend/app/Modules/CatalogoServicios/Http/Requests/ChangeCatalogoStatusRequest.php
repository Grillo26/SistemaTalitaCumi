<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cambio de estado de área, servicio o tipo (RF-C02, RF-C12, RN-C10).
 *
 * El motivo es obligatorio tanto al activar como al desactivar.
 */
final class ChangeCatalogoStatusRequest extends FormRequest
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
            'motivo.required' => 'El motivo es obligatorio para cambiar el estado.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
