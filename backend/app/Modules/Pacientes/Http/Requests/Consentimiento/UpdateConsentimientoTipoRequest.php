<?php

namespace App\Modules\Pacientes\Http\Requests\Consentimiento;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de un tipo de consentimiento (04.03 Fase B).
 *
 * El código es inmutable una vez creado.
 */
final class UpdateConsentimientoTipoRequest extends FormRequest
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
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'orden' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'activo' => ['sometimes', 'boolean'],
            'version' => ['required', 'integer', 'min:1'],
            'codigo' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo es obligatorio.',
            'version.required' => 'La versión del registro es obligatoria.',
            'codigo.prohibited' => 'El código no se modifica después de crear el tipo.',
        ];
    }
}
