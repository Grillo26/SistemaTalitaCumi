<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de tarifa (RF-T03 a RF-T07).
 */
final class StoreTarifaRequest extends FormRequest
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
            'categoria_tarifa_id' => ['required', 'integer', 'exists:categoria_tarifa,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'monto' => ['required', 'string', 'max:15', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'vigente_desde' => ['required', 'date_format:Y-m-d'],
            'vigente_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:vigente_desde'],
            'activo' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'servicio_id.required' => 'El servicio es obligatorio.',
            'servicio_id.exists' => 'El servicio seleccionado no existe.',
            'categoria_tarifa_id.required' => 'La categoría es obligatoria.',
            'categoria_tarifa_id.exists' => 'La categoría seleccionada no existe.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.regex' => 'El monto debe ser un número no negativo con hasta dos decimales.',
            'vigente_desde.required' => 'La fecha de inicio de vigencia es obligatoria.',
            'vigente_desde.date_format' => 'La fecha de inicio debe tener formato AAAA-MM-DD.',
            'vigente_hasta.date_format' => 'La fecha de fin debe tener formato AAAA-MM-DD.',
            'vigente_hasta.after_or_equal' => 'La fecha de fin no puede ser anterior al inicio.',
            'activo.prohibited' => 'El estado se gestiona con el cierre de vigencia.',
        ];
    }
}
