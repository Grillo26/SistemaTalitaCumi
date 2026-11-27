<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-012.
 */
final class ReporteEstadoRequest extends FormRequest
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
        $estados = array_values(array_unique(array_merge(
            EstadoPaciente::values(),
            EstadoInscripcionSala::values(),
            EstadoPaqueteSesiones::values(),
            EstadoInscripcion::values(),
        )));

        return [
            'tipo' => ['nullable', Rule::in(['pacientes', 'inscripciones'])],
            'search' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', Rule::in($estados)],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'servicio_id' => ['nullable', 'integer', Rule::exists('servicio', 'id')],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:periodo_desde',
                'after_or_equal:periodo_desde',
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
