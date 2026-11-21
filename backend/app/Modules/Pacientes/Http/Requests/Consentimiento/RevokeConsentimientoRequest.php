<?php

namespace App\Modules\Pacientes\Http\Requests\Consentimiento;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revocación de un consentimiento (04.03 Fase B).
 */
final class RevokeConsentimientoRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de revocación es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
        ];
    }
}
