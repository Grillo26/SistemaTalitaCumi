<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edición de tipo de sala (RF-002).
 */
final class UpdateTipoSalaRequest extends FormRequest
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
            'nombre' => ['sometimes', 'required', 'string', 'max:100'],
            'version' => ['required', 'integer', 'min:1'],
            'activo' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->exists('nombre')) {
                    $validator->errors()->add('nombre', 'Enviá al menos un campo para actualizar.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre no puede quedar vacío.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
        ];
    }
}
