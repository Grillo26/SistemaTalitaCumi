<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de tipo de servicio (RN-C09).
 */
final class UpdateTipoServicioRequest extends FormRequest
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
            'nombre' => ['sometimes', 'filled', 'string', 'max:100'],
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
            'version.required' => 'La versión del registro es obligatoria.',
            'activo.prohibited' => 'El estado se cambia desde la acción de activar o desactivar.',
        ];
    }
}
