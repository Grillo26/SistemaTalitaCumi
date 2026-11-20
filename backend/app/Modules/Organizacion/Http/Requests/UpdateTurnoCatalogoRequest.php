<?php

namespace App\Modules\Organizacion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edición de turno operativo (RF-003).
 */
final class UpdateTurnoCatalogoRequest extends FormRequest
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
            'nombre' => ['sometimes', 'required', 'string', 'max:50'],
            'orden' => ['sometimes', 'required', 'integer', 'min:0'],
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
                if (! $this->exists('nombre') && ! $this->exists('orden')) {
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
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden no puede ser negativo.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
        ];
    }
}
