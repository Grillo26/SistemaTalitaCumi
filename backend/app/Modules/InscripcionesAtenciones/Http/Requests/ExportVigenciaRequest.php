<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Vigencia y Renovación] RF-010 / CA-010.
 */
final class ExportVigenciaRequest extends FormRequest
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
            'formato' => ['required', Rule::in(['pdf', 'excel'])],
            'search' => ['nullable', 'string', 'max:100'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicio,id'],
            'tipo_entidad' => ['nullable', Rule::enum(TipoEntidadVigencia::class)],
            'estado' => ['nullable', Rule::enum(EstadoVigencia::class)],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_desde', 'after_or_equal:periodo_desde'],
        ];
    }
}
