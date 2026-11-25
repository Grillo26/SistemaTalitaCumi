<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Designación de tutor principal (04.02/RF-005).
 */
final class SetPrincipalTutorRequest extends FormRequest
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
            'motivo' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
