<?php

namespace App\Modules\Pacientes\Http\Requests\Consentimiento;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de un tipo de consentimiento (04.03 Fase B).
 */
final class StoreConsentimientoTipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo')) {
            $this->merge([
                'codigo' => strtolower(trim((string) $this->input('codigo'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', 'unique:consentimiento_tipo,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del tipo es obligatorio.',
            'codigo.regex' => 'El código solo admite minúsculas, números y guiones bajos.',
            'codigo.unique' => 'Ya existe un tipo con ese código.',
            'nombre.required' => 'El nombre del tipo es obligatorio.',
        ];
    }
}
