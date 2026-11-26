<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-006.
 */
final class PreviewMontoEntrevistaRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
