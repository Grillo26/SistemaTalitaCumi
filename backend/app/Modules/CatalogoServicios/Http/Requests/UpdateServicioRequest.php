<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de servicio (RF-C11).
 */
final class UpdateServicioRequest extends FormRequest
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
            'area_id' => ['sometimes', 'integer', 'exists:area,id'],
            'tipo_servicio_id' => ['sometimes', 'integer', 'exists:tipo_servicio,id'],
            'nombre' => ['sometimes', 'filled', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'duracion_minutos' => ['sometimes', 'integer', 'min:1', 'max:480'],
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
