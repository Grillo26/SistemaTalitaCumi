<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Vigencia y Renovación] RF-004 / RF-008 / RF-009 / RF-014.
 */
final class IndexVigenciaRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicio,id'],
            'tipo_entidad' => ['nullable', Rule::enum(TipoEntidadVigencia::class)],
            'estado' => ['nullable', Rule::enum(EstadoVigencia::class)],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_desde', 'after_or_equal:periodo_desde'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:fecha_fin,fecha_inicio,monto_esperado,paciente_nombre,tipo_entidad'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periodo_hasta.after_or_equal' => 'La fecha hasta no puede ser anterior a la fecha desde.',
        ];
    }
}
