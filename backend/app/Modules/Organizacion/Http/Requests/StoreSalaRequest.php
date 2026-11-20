<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de sala grupal (RF-004/RF-005 / RN-001, RN-002, RN-004).
 */
final class StoreSalaRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'area_id' => ['required', 'integer', 'exists:area,id'],
            'tipo_sala_id' => ['required', 'integer', 'exists:tipo_sala,id'],
            'turno_catalogo_id' => ['required', 'integer', 'exists:turno_catalogo,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'area_id.required' => 'El área es obligatoria.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'tipo_sala_id.required' => 'El tipo de sala es obligatorio.',
            'tipo_sala_id.exists' => 'El tipo de sala seleccionado no existe.',
            'turno_catalogo_id.required' => 'El turno es obligatorio.',
            'turno_catalogo_id.exists' => 'El turno seleccionado no existe.',
            'nombre.required' => 'El nombre visible de la sala es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
        ];
    }
}
