<?php

namespace App\Modules\Pacientes\Http\Requests\Registro;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cambio de estado del paciente (04.01/RF-008 / 04.01/RN-005).
 */
final class ChangePacienteStatusRequest extends FormRequest
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
            'estado' => ['required', Rule::in(EstadoPaciente::values())],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'El estado destino es obligatorio.',
            'estado.in' => 'El estado destino no es válido.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
