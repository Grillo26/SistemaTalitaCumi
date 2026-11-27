<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-006.
 */
final class PreviewMontoDiagnosticoRequest extends FormRequest
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
            'tipo_diagnostico_id' => ['required', 'integer', 'exists:tipo_diagnostico,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
