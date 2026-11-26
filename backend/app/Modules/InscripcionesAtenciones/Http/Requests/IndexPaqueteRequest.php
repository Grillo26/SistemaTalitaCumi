<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Paquetes Sesiones Individuales] RF-011, RF-017.
 */
final class IndexPaqueteRequest extends FormRequest
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
            'profesional_id' => ['nullable', 'integer', 'exists:profesional,id'],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_hasta'],
            'periodo_hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:periodo_desde', 'after_or_equal:periodo_desde'],
            'estado' => ['nullable', 'in:activo,vencido,retirado,en_pausa,renovado'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:fecha_inicio,fecha_fin,estado,monto_esperado,cantidad_sesiones,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
