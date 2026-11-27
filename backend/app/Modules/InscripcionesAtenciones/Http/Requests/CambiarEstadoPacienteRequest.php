<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-003 / RF-006 / CA-001.
 */
final class CambiarEstadoPacienteRequest extends FormRequest
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
            'estado' => ['required', Rule::in([EstadoPaciente::ACTIVO->value, EstadoPaciente::EN_PAUSA->value])],
            'estado_esperado' => ['required', Rule::enum(EstadoPaciente::class)],
            'motivo' => ['nullable', 'string', 'max:50'],
            'motivo_detalle' => ['nullable', 'string', 'max:500'],
        ];
    }
}
