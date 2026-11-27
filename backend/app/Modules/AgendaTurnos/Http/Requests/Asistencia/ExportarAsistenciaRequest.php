<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Asistencia;

use Illuminate\Validation\Rule;

/**
 * 07.04/RF-010, 07.04/RNF-010.
 */
final class ExportarAsistenciaRequest extends IndicadoresAsistenciaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'formato' => ['nullable', Rule::in(['csv', 'pdf'])],
        ];
    }
}
