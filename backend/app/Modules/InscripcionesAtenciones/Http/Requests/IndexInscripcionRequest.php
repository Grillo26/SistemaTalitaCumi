<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Inscripción Salas Grupales] RF-007, RF-016.
 */
final class IndexInscripcionRequest extends FormRequest
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
            'sala_id' => ['nullable', 'integer', 'exists:sala,id'],
            'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'],
            'periodo_mes' => ['nullable', 'integer', 'between:1,12', 'required_with:periodo_anio'],
            'periodo_anio' => ['nullable', 'integer', 'digits:4', 'required_with:periodo_mes'],
            'estado' => ['nullable', 'in:activa,vencida,retirada,en_pausa,renovada'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:fecha_inicio,fecha_fin,estado,monto_esperado,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
