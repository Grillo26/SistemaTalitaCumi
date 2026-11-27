<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Programación de Turnos] RF-007 / RF-008.
 *
 * Acepta un lote completo (`lote_generacion_id`) o un subconjunto explícito
 * de turnos (`turno_ids`). El servicio valida que todos sean futuros
 * Programados y aplica el cambio todo o nada.
 */
final class AsignarProfesionalRequest extends FormRequest
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
            'profesional_id' => ['required', 'integer', 'exists:profesional,id'],
            'lote_generacion_id' => [
                'required_without:turno_ids',
                'nullable',
                'integer',
                'exists:turno_lote_generacion,id',
            ],
            'turno_ids' => [
                'required_without:lote_generacion_id',
                'nullable',
                'array',
                'min:1',
            ],
            'turno_ids.*' => ['integer', 'distinct', 'exists:turno,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profesional_id.required' => 'Seleccioná un profesional.',
            'lote_generacion_id.required_without' => 'Indicá un lote o al menos un turno.',
            'turno_ids.required_without' => 'Indicá un lote o al menos un turno.',
        ];
    }
}
