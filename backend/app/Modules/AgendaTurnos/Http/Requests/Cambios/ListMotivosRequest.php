<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Reprogramación y Cancelación] RF-003 / RNF-011.
 */
final class ListMotivosRequest extends FormRequest
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
            'accion' => ['required', Rule::in(['reprogramar', 'cancelar'])],
        ];
    }
}
