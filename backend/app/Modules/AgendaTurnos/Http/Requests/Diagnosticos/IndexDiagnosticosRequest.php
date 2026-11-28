<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Diagnosticos;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 07.05/RF-006 / 07.05/RF-008 / 07.05/RN-008.
 */
class IndexDiagnosticosRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
            'desde' => ['nullable', 'date_format:Y-m-d', 'required_with:hasta'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:desde', 'after_or_equal:desde'],
            'tipo_diagnostico_id' => ['nullable', 'integer', 'exists:tipo_diagnostico,id'],
            'estado_general' => ['nullable', Rule::in(EstadoGeneralDiagnostico::values())],
            'profesional_id' => ['nullable', 'integer', 'exists:profesional,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
