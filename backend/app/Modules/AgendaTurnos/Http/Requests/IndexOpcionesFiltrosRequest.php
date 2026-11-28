<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [Agenda Semanal] RF-005, RF-011.
 */
final class IndexOpcionesFiltrosRequest extends FormRequest
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
            'sucursal_id' => ['nullable', 'integer', 'min:1', 'exists:sucursal,id'],
        ];
    }
}
