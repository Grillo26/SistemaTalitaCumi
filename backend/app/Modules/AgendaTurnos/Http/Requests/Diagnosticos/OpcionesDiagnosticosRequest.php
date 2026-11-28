<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Diagnosticos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 07.05/RF-006 / 07.05/RF-008.
 */
final class OpcionesDiagnosticosRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursal,id'],
        ];
    }
}
