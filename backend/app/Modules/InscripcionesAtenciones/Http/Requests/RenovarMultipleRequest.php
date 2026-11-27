<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Vigencia y Renovación] RF-006 / RF-016 / CA-005.
 */
final class RenovarMultipleRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.tipo_entidad' => ['required', Rule::enum(TipoEntidadVigencia::class)],
            'items.*.entidad_id' => ['required', 'integer', 'min:1'],
            'items.*.fecha_inicio' => ['sometimes', 'date_format:Y-m-d'],
            'items.*.fecha_fin' => ['sometimes', 'date_format:Y-m-d'],
            'items.*.servicio_id' => ['sometimes', 'integer', 'exists:servicio,id'],
            'items.*.categoria_tarifa_id' => ['sometimes', 'integer', 'exists:categoria_tarifa,id'],
            'items.*.cantidad_sesiones' => ['sometimes', 'integer', 'min:1'],
            'items.*.dias_semana' => ['sometimes', 'array', 'min:1'],
            'items.*.dias_semana.*' => ['integer', 'between:1,6', 'distinct'],
            'items.*.hora' => ['sometimes', 'date_format:H:i'],
            'items.*.duracion_minutos' => ['sometimes', 'integer', 'min:1'],
            'items.*.profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesional,id'],
            'items.*.observaciones' => ['sometimes', 'nullable', 'string', 'max:500'],
            'items.*.tarifa_id_vista' => ['required', 'integer', 'min:1'],
            'items.*.monto_visto' => ['required', 'string', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
            'items.*.confirmar_tarifa_cambio' => ['sometimes', 'boolean'],
        ];
    }
}
