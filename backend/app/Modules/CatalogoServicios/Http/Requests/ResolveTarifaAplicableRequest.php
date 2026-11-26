<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Consulta de tarifa aplicable (RF-T08 / RF-T16).
 */
final class ResolveTarifaAplicableRequest extends FormRequest
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'servicio_id.required' => 'El servicio es obligatorio.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'categoria_tarifa_id.required' => 'La categoría es obligatoria.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date_format' => 'La fecha debe tener formato AAAA-MM-DD.',
        ];
    }
}
