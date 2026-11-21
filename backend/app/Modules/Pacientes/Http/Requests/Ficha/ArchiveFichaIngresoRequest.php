<?php

namespace App\Modules\Pacientes\Http\Requests\Ficha;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Archivo de ficha con motivo (04.03/RF-014, 04.03/RN-005).
 */
final class ArchiveFichaIngresoRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'max:1000'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de archivo es obligatorio.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
