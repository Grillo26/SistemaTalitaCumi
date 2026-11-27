<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Asistencia;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 07.04/RF-001, 07.04/RF-003, 07.04/RF-007.
 */
final class RegistrarAsistenciaRequest extends FormRequest
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
            'version' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
