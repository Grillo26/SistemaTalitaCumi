<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de tarifa futura o vigente (RF-T11).
 *
 * `vigente_hasta` se modifica solo con la acción de cierre y su motivo.
 */
final class UpdateTarifaRequest extends FormRequest
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
            'servicio_id' => ['sometimes', 'integer', 'exists:servicio,id'],
            'categoria_tarifa_id' => ['sometimes', 'integer', 'exists:categoria_tarifa,id'],
            'sucursal_id' => ['sometimes', 'nullable', 'integer', 'exists:sucursal,id'],
            'monto' => ['sometimes', 'string', 'max:15', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'vigente_desde' => ['sometimes', 'date_format:Y-m-d'],
            'version' => ['required', 'integer', 'min:1'],
            'vigente_hasta' => ['prohibited'],
            'activo' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monto.regex' => 'El monto debe ser un número no negativo con hasta dos decimales.',
            'version.required' => 'La versión del registro es obligatoria.',
            'vigente_hasta.prohibited' => 'La vigencia se cierra con la acción Cerrar vigencia.',
            'activo.prohibited' => 'El estado se gestiona con el cierre de vigencia.',
        ];
    }
}
