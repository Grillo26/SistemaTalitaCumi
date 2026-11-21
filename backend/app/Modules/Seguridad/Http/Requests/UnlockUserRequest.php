<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Desbloqueo manual de cuenta (02/RF-A06).
 */
final class UnlockUserRequest extends FormRequest
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
            'motivo.required' => 'El motivo del desbloqueo es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
            'version.required' => 'La versión del usuario es obligatoria.',
        ];
    }
}
