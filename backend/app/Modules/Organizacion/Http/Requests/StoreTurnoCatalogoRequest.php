<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de turno operativo (RF-003).
 */
final class StoreTurnoCatalogoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:50'],
            'orden' => ['required', 'integer', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del turno es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'orden.required' => 'El orden de visualización es obligatorio.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden no puede ser negativo.',
        ];
    }
}
