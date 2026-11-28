<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Diagnosticos;

use Illuminate\Validation\Rule;

/**
 * 07.05/RF-008 / 07.05/FP-04 / 07.05/CU-03 FE-01.
 *
 * El período es obligatorio para exportar. El límite de filas se valida en la
 * Query con un 422 explícito.
 */
final class ExportarDiagnosticosRequest extends IndexDiagnosticosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'formato' => ['nullable', Rule::in(['csv', 'pdf'])],
        ];
    }
}
