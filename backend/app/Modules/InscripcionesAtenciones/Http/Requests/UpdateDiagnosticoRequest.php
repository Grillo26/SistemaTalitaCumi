<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-010 / CA-006.
 */
final class UpdateDiagnosticoRequest extends FormRequest
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
            'profesionales' => ['sometimes', 'array'],
            'profesionales.*' => ['integer', 'distinct', 'exists:profesional,id'],
            'notas_admin' => ['sometimes', 'nullable', 'string', 'max:500'],
            'paciente_id' => ['prohibited'],
            'sucursal_id' => ['prohibited'],
            'servicio_id' => ['prohibited'],
            'tipo_diagnostico_id' => ['prohibited'],
            'categoria_tarifa_id' => ['prohibited'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
            'estado_general' => ['prohibited'],
            'entrevista_previa_id' => ['prohibited'],
            'omitio_entrevista' => ['prohibited'],
            'motivo_omision_entrevista' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasAny(['fecha_inicio', 'fecha_fin', 'profesionales', 'notas_admin'])) {
                $validator->errors()->add('atencion', 'Indicá al menos un dato para modificar.');
            }
        });
    }
}
