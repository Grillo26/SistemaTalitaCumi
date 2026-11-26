<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Paquetes Sesiones Individuales] RF-013, CU-02.
 */
final class RenovarPaqueteRequest extends FormRequest
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
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'cantidad_sesiones' => ['nullable', 'integer', 'min:1'],
            'dias_semana' => ['nullable', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:1,6', 'distinct'],
            'hora' => ['nullable', 'date_format:H:i'],
            'duracion_minutos' => ['nullable', 'integer', 'min:1'],
            'profesional_id' => ['nullable', 'integer', 'exists:profesional,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'tarifa_id_esperada' => ['nullable', 'integer'],
            'confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
            'paciente_id' => ['prohibited'],
            'tutor_responsable_id' => ['prohibited'],
            'sucursal_id' => ['prohibited'],
            'servicio_id' => ['prohibited'],
            'categoria_tarifa_id' => ['prohibited'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('fecha_inicio') && ! $this->filled('fecha_fin')) {
                $validator->errors()->add(
                    'fecha_fin',
                    'Si ajustás la fecha de inicio, también tenés que indicar la fecha de fin.',
                );
            }

            if ($this->filled('fecha_fin') && ! $this->filled('fecha_inicio')) {
                $validator->errors()->add(
                    'fecha_inicio',
                    'Si ajustás la fecha de fin, también tenés que indicar la fecha de inicio.',
                );
            }
        });
    }
}
