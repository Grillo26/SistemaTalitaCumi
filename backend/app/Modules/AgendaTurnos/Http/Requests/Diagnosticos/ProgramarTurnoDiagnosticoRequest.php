<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Diagnosticos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RF-015.
 */
final class ProgramarTurnoDiagnosticoRequest extends FormRequest
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
            'profesional_id' => ['required', 'integer', 'exists:profesional,id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'duracion_minutos' => ['nullable', 'integer', 'min:1', 'max:480'],
        ];
    }
}
