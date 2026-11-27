<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Paquetes Sesiones Individuales] RF-007, RF-008, RN-011.
 */
final class PreviewMontoPaqueteRequest extends FormRequest
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
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'cantidad_sesiones' => ['nullable', 'integer', 'min:1'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['integer', 'between:1,6', 'distinct'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'tarifa_id_esperada' => ['nullable', 'integer'],
        ];
    }
}
