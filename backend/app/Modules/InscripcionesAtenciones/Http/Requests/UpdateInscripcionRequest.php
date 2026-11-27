<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Inscripción Salas Grupales] RF-009, RN-007, RN-011.
 */
final class UpdateInscripcionRequest extends FormRequest
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
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],
            'tarifa_id_esperada' => ['sometimes', 'nullable', 'integer'],
            'confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
            'paciente_id' => ['prohibited'],
            'tutor_responsable_id' => ['prohibited'],
            'sucursal_id' => ['prohibited'],
            'sala_id' => ['prohibited'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasAny(['fecha_inicio', 'fecha_fin', 'servicio_id', 'categoria_tarifa_id', 'observaciones'])) {
                $validator->errors()->add('inscripcion', 'Indicá al menos un dato para modificar.');
            }

            if ($this->filled('fecha_inicio') && ! $this->filled('fecha_fin')) {
                $validator->errors()->add(
                    'fecha_fin',
                    'Si modificás la fecha de inicio, también tenés que indicar la fecha de fin.',
                );
            }
        });
    }
}
