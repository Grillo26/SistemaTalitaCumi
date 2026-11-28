<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Asistencia;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 07.04/RF-004, 07.04/RF-005, 07.04/RF-013.
 */
final class CorregirAsistenciaRequest extends FormRequest
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
            'estado' => ['required', Rule::in([
                EstadoTurno::ASISTIO->value,
                EstadoTurno::FALTO->value,
            ])],
            'version' => ['required', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
