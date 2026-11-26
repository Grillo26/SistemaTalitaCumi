<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de servicio (RF-C05 a RF-C07).
 */
final class StoreServicioRequest extends FormRequest
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
            'area_id' => ['required', 'integer', 'exists:area,id'],
            'tipo_servicio_id' => ['required', 'integer', 'exists:tipo_servicio,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'duracion_minutos' => ['required', 'integer', 'min:1', 'max:480'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'area_id.required' => 'El área es obligatoria.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'tipo_servicio_id.required' => 'El tipo de servicio es obligatorio.',
            'tipo_servicio_id.exists' => 'El tipo de servicio seleccionado no existe.',
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'duracion_minutos.required' => 'La duración es obligatoria.',
            'duracion_minutos.min' => 'La duración debe ser mayor que cero.',
            'duracion_minutos.max' => 'La duración no puede superar los 480 minutos.',
        ];
    }
}
