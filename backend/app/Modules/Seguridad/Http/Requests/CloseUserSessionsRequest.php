<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cierre remoto de sesiones (02/RF-A09 / 02/RN-10).
 */
final class CloseUserSessionsRequest extends FormRequest
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
            'motivo.required' => 'El motivo del cierre de sesiones es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
        ];
    }
}
