<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de tipo de diagnóstico y especialidades (RF-D04).
 */
final class UpdateTipoDiagnosticoRequest extends FormRequest
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
            'servicio_id' => ['sometimes', 'integer', 'exists:servicio,id'],
            'nombre' => ['sometimes', 'filled', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'dias_estimados' => ['sometimes', 'integer', 'min:1'],
            'version' => ['required', 'integer', 'min:1'],
            'activo' => ['prohibited'],
            'especialidades' => ['sometimes', 'array', 'min:1'],
            'especialidades.*.especialidad_id' => [
                'required',
                'integer',
                'exists:especialidad,id',
                'distinct',
            ],
            'especialidades.*.cantidad_minima' => ['required', 'integer', 'min:1'],
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
            'especialidades.min' => 'Agregá al menos una especialidad requerida.',
            'especialidades.*.especialidad_id.distinct' => 'No repitas la misma especialidad.',
        ];
    }
}
