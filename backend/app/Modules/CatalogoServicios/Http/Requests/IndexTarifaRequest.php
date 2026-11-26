<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado y filtros de tarifas (RF-T09).
 */
final class IndexTarifaRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:150'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicio,id'],
            'categoria_tarifa_id' => ['nullable', 'integer', 'exists:categoria_tarifa,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'solo_global' => ['nullable', 'boolean'],
            'estado_vigencia' => ['nullable', 'in:vigente,futura,cerrada'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:servicio,categoria,sucursal,monto,vigente_desde,vigente_hasta,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
