<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Reprogramación y Cancelación] RF-002 / RF-003 / RF-008 / RF-014.
 */
final class CancelacionRequest extends FormRequest
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
            'motivo_id' => ['required', 'integer', 'exists:motivo_turno,id'],
            'motivo_texto' => ['nullable', 'string', 'max:500'],
            'estado_esperado' => ['required', Rule::in(EstadoTurno::values())],
        ];
    }
}
