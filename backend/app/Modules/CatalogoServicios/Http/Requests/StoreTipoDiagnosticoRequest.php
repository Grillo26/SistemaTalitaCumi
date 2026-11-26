<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de tipo de diagnóstico (RF-D01 / RF-D02 / RN-D08).
 */
final class StoreTipoDiagnosticoRequest extends FormRequest
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
            'servicio_id' => ['required', 'integer', 'exists:servicio,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'dias_estimados' => ['sometimes', 'integer', 'min:1'],
            'activo' => ['sometimes', 'boolean'],
            'especialidades' => ['required', 'array', 'min:1'],
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
            'servicio_id.required' => 'El servicio es obligatorio.',
            'servicio_id.exists' => 'El servicio seleccionado no existe.',
            'nombre.required' => 'El nombre del tipo de diagnóstico es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'dias_estimados.min' => 'Los días estimados deben ser mayores o iguales a 1.',
            'especialidades.required' => 'Agregá al menos una especialidad requerida.',
            'especialidades.min' => 'Agregá al menos una especialidad requerida.',
            'especialidades.*.especialidad_id.required' => 'Seleccioná una especialidad.',
            'especialidades.*.especialidad_id.exists' => 'La especialidad seleccionada no existe.',
            'especialidades.*.especialidad_id.distinct' => 'No repitas la misma especialidad.',
            'especialidades.*.cantidad_minima.required' => 'La cantidad es obligatoria.',
            'especialidades.*.cantidad_minima.min' => 'La cantidad debe ser mayor o igual a 1.',
        ];
    }
}
