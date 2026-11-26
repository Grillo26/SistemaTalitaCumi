<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de área (RF-C01 a RF-C04).
 */
final class StoreAreaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del área es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'color.required' => 'El color es obligatorio.',
            'color.regex' => 'El color debe tener formato hexadecimal #RGB o #RRGGBB.',
            'descripcion.max' => 'La descripción no puede superar los 2000 caracteres.',
        ];
    }
}
