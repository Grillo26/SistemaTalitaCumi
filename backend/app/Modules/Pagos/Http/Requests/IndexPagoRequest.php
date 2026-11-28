<?php

namespace App\Modules\Pagos\Http\Requests;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Queries\ListPagosQuery;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros de listado y exportación (08.01/RF-008). `per_page` máximo 50.
 */
final class IndexPagoRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d'],
            'periodo_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:periodo_desde'],
            'paciente_id' => ['nullable', 'integer', 'min:1'],
            'numero' => ['nullable', 'string', 'max:30'],
            'medio_pago' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'in:'.implode(',', EstadoPago::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:'.implode(',', ListPagosQuery::SORT_PERMITIDOS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
