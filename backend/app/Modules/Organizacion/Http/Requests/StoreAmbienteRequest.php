<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Enums\UsoAmbiente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de ambiente (RF-006/RF-007 / RN-004).
 */
final class StoreAmbienteRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:sucursal,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'uso' => ['required', Rule::enum(UsoAmbiente::class)],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'nombre.required' => 'El nombre del ambiente es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'uso.required' => 'El uso previsto es obligatorio.',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
        ];
    }
}
