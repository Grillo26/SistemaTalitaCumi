<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-011 / RF-014 / CA-011 / CA-012.
 */
final class IndexEstadoInscripcionRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'tipo_entidad' => ['nullable', Rule::enum(TipoEntidadVigencia::class)],
            'estado' => ['nullable', Rule::enum(EstadoInscripcion::class)],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'servicio_id' => ['nullable', 'integer', Rule::exists('servicio', 'id')],
            'paciente_id' => ['nullable', 'integer', Rule::exists('paciente', 'id')],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:periodo_desde',
                'after_or_equal:periodo_desde',
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:fecha_fin,fecha_inicio,monto_esperado,tipo_entidad,paciente_nombre'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
