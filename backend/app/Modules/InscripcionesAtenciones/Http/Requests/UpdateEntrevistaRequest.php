<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-010 / CA-006.
 */
final class UpdateEntrevistaRequest extends FormRequest
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
            'fecha' => ['sometimes', 'date_format:Y-m-d'],
            'notas_admin' => ['sometimes', 'nullable', 'string', 'max:500'],
            'paciente_id' => ['prohibited'],
            'tutor_id' => ['prohibited'],
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
            if (! $this->hasAny(['fecha', 'notas_admin'])) {
                $validator->errors()->add('atencion', 'Indicá al menos un dato para modificar.');
            }
        });
    }
}
