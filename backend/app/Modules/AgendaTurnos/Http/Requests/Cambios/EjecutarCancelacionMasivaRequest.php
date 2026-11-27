<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

/**
 * [Reprogramación y Cancelación] RF-011 / RN-009 / CA-007.
 *
 * `turno_ids` es el conjunto exacto que el usuario vio en el preview.
 */
final class EjecutarCancelacionMasivaRequest extends CancelacionMasivaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'turno_ids' => ['required', 'array', 'min:1'],
            'turno_ids.*' => ['integer', 'distinct', 'exists:turno,id'],
            'confirmar' => ['required', 'accepted'],
        ];
    }
}
