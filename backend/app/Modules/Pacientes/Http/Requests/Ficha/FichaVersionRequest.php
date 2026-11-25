<?php

namespace App\Modules\Pacientes\Http\Requests\Ficha;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Versión para transiciones sin datos adicionales (04.03/RNF-007 bis).
 */
final class FichaVersionRequest extends FormRequest
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
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
