<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\MotivoPaciente;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-005 / CU-02 / CA-005 / CA-006.
 */
final class RetirarPacienteRequest extends FormRequest
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
            'estado_esperado' => ['required', Rule::enum(EstadoPaciente::class)],
            'motivo' => ['required', Rule::in(MotivoPaciente::values())],
            'motivo_detalle' => ['nullable', 'string', 'max:500'],
            'fecha_baja' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'inscripciones' => ['present', 'array'],
            'inscripciones.*.tipo_entidad' => ['required', Rule::enum(TipoEntidadVigencia::class)],
            'inscripciones.*.id' => ['required', 'integer', 'min:1'],
            'inscripciones.*.estado_esperado' => ['required', Rule::enum(EstadoInscripcion::class)],
        ];
    }
}
