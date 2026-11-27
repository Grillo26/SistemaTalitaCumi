<?php

namespace App\Modules\AgendaTurnos\Http\Requests\Cambios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Reprogramación y Cancelación] RF-015.
 */
final class ContactoWhatsappRequest extends FormRequest
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
            'accion' => ['nullable', Rule::in(['reprogramado', 'cancelado'])],
        ];
    }
}
