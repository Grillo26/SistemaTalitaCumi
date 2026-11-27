<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-002, RF-003, RF-004, RF-005, RF-006,
 * RF-007, RF-015.
 */
final class StoreDiagnosticoRequest extends FormRequest
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
            'paciente_id' => ['required', 'integer', 'exists:paciente,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'tipo_diagnostico_id' => ['required', 'integer', 'exists:tipo_diagnostico,id'],
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'profesionales' => ['sometimes', 'array'],
            'profesionales.*' => ['integer', 'distinct', 'exists:profesional,id'],
            'omitio_entrevista' => ['sometimes', 'boolean'],
            'motivo_omision_entrevista' => ['nullable', 'string', 'max:500'],
            'entrevista_previa_id' => ['nullable', 'integer', 'exists:atencion_entrevista,id'],
            'notas_admin' => ['nullable', 'string', 'max:500'],
            'tarifa_id_esperada' => ['nullable', 'integer'],
            'confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
            'servicio_id' => ['prohibited'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
            'estado_general' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paciente_id.required' => 'El paciente es obligatorio.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'tipo_diagnostico_id.required' => 'El tipo de diagnóstico es obligatorio.',
            'categoria_tarifa_id.required' => 'La categoría de tarifa es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior al inicio.',
            'profesionales.*.exists' => 'Uno de los profesionales indicados no existe.',
            'notas_admin.max' => 'Las notas administrativas no pueden superar los 500 caracteres.',
            'motivo_omision_entrevista.max' => 'El motivo de omisión no puede superar los 500 caracteres.',
            'servicio_id.prohibited' => 'El servicio se deriva del tipo de diagnóstico; no se acepta desde el cliente.',
            'monto_esperado.prohibited' => 'El monto lo calcula el sistema; no se acepta desde el cliente.',
        ];
    }
}
