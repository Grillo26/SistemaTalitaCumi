<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] RF-003.
 */
final class BuscarEntrevistaPreviaRequest extends FormRequest
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
            'paciente_id' => ['required', 'integer', 'exists:paciente,id'],
        ];
    }
}
