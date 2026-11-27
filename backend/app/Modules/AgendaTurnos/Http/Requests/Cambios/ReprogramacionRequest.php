<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Reprogramación y Cancelación] RF-001 / RF-003 / RF-004 / RF-014.
 */
final class ReprogramacionRequest extends FormRequest
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
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'motivo_id' => ['required', 'integer', 'exists:motivo_turno,id'],
            'motivo_texto' => ['nullable', 'string', 'max:500'],
            'estado_esperado' => ['required', Rule::in(EstadoTurno::values())],
            'profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesional,id'],
            'sala_id' => ['sometimes', 'nullable', 'integer', 'exists:sala,id'],
            'confirmar_advertencias' => ['sometimes', 'boolean'],
        ];
    }
}
