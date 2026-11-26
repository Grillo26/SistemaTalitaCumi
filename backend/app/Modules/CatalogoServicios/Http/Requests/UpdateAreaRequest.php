<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de área (RF-C02 a RF-C04 / RF-C11 por analogía).
 */
final class UpdateAreaRequest extends FormRequest
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
            'nombre' => ['sometimes', 'filled', 'string', 'max:100'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'color' => ['sometimes', 'filled', 'string', 'max:7', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'version' => ['required', 'integer', 'min:1'],
            'activo' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.regex' => 'El color debe tener formato hexadecimal #RGB o #RRGGBB.',
            'version.required' => 'La versión del registro es obligatoria.',
            'activo.prohibited' => 'El estado se cambia desde la acción de activar o desactivar.',
        ];
    }
}
