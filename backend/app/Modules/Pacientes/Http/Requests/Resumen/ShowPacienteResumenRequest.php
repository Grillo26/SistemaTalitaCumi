<?php

namespace App\Modules\Pacientes\Http\Requests\Resumen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Contexto opcional de la ficha resumen (04.04/RF-011).
 */
final class ShowPacienteResumenRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sucursal_id.exists' => 'La sucursal de contexto no existe.',
        ];
    }
}
