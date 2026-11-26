<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Vigencia y Renovación] RF-005 / RF-006 / RN-005 / CA-004 / CA-005.
 */
final class RenovarVigenciaRequest extends FormRequest
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
            'fecha_inicio' => ['sometimes', 'date_format:Y-m-d'],
            'fecha_fin' => ['sometimes', 'date_format:Y-m-d'],
            'servicio_id' => ['sometimes', 'integer', 'exists:servicio,id'],
            'categoria_tarifa_id' => ['sometimes', 'integer', 'exists:categoria_tarifa,id'],
            'cantidad_sesiones' => ['sometimes', 'integer', 'min:1'],
            'dias_semana' => ['sometimes', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:1,6', 'distinct'],
            'hora' => ['sometimes', 'date_format:H:i'],
            'duracion_minutos' => ['sometimes', 'integer', 'min:1'],
            'profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesional,id'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tarifa_id_vista' => ['required', 'integer', 'min:1'],
            'monto_visto' => ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
            'paciente_id' => ['prohibited'],
            'tutor_responsable_id' => ['prohibited'],
            'sucursal_id' => ['prohibited'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('fecha_inicio') && ! $this->filled('fecha_fin')) {
                $validator->errors()->add('fecha_fin', 'Si ajustás la fecha de inicio, indicá también la fecha de fin.');
            }

            if ($this->filled('fecha_fin') && ! $this->filled('fecha_inicio')) {
                $validator->errors()->add('fecha_inicio', 'Si ajustás la fecha de fin, indicá también la fecha de inicio.');
            }
        });
    }
}
