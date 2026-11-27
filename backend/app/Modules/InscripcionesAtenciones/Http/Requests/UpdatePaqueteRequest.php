<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Paquetes Sesiones Individuales] RF-012.
 */
final class UpdatePaqueteRequest extends FormRequest
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
            'profesional_id' => ['nullable', 'integer', 'exists:profesional,id'],
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['integer', 'between:1,6', 'distinct'],
            'hora' => ['required', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'paciente_id' => ['prohibited'],
            'tutor_responsable_id' => ['prohibited'],
            'sucursal_id' => ['prohibited'],
            'servicio_id' => ['prohibited'],
            'categoria_tarifa_id' => ['prohibited'],
            'cantidad_sesiones' => ['prohibited'],
            'duracion_minutos' => ['prohibited'],
            'fecha_inicio' => ['prohibited'],
            'fecha_fin' => ['prohibited'],
            'monto_esperado' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }
}
