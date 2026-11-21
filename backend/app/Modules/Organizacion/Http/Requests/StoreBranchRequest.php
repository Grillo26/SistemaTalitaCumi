<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de sucursal.
 *
 * RF-001, RF-002, RF-003 y RF-013.
 * La unicidad se valida en la Action contra la base de datos; los índices
 * funcionales son el respaldo definitivo.
 */
final class StoreBranchRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:1000'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => 'El código no puede superar los 50 caracteres.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'email.email' => 'El correo no tiene un formato válido.',
        ];
    }
}
