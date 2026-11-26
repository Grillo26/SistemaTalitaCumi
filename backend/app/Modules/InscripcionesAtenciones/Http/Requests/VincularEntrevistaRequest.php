<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Entrevista y Diagnóstico] CU-03 FA-01.
 */
final class VincularEntrevistaRequest extends FormRequest
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
            'entrevista_id' => ['required', 'integer', 'exists:atencion_entrevista,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entrevista_id.required' => 'La entrevista es obligatoria.',
            'entrevista_id.exists' => 'La entrevista indicada no existe.',
        ];
    }
}
