<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Diagnosticos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 07.05/RF-007 / 07.05/CU-02 FA-01 / 07.05/CA-007.
 */
final class CancelarDiagnosticoRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
