<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Diagnosticos;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 07.05/RF-007 / 07.05/RF-012 / 07.05/RN-005 / 07.05/RN-007.
 *
 * La cancelación tiene su propio endpoint con motivo obligatorio.
 */
final class ActualizarEstadoGeneralRequest extends FormRequest
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
            'estado_general' => [
                'required',
                Rule::in([
                    EstadoGeneralDiagnostico::INCOMPLETO->value,
                    EstadoGeneralDiagnostico::EN_PROCESO->value,
                    EstadoGeneralDiagnostico::COMPLETO->value,
                ]),
            ],
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
