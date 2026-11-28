<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Reprogramación y Cancelación] RF-011 / RN-009 / CA-007.
 */
class CancelacionMasivaRequest extends FormRequest
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
            'paciente_id' => ['required', 'integer', 'exists:paciente,id'],
            'entidades' => ['required', 'array', 'min:1'],
            'entidades.*.tipo' => ['required', Rule::in(['inscripcion_sala', 'paquete_sesiones'])],
            'entidades.*.id' => ['required', 'integer', 'min:1'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicio,id'],
            'motivo_id' => ['required', 'integer', 'exists:motivo_turno,id'],
            'motivo_texto' => ['nullable', 'string', 'max:500'],
        ];
    }
}
