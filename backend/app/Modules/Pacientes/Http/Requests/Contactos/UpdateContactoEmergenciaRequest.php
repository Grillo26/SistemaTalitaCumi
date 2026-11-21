<?php

namespace App\Modules\Pacientes\Http\Requests\Contactos;

use App\Modules\Pacientes\Rules\TutorPhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de contacto de emergencia (04.02/RF-012).
 */
final class UpdateContactoEmergenciaRequest extends FormRequest
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
            'nombre_completo' => ['sometimes', 'string', 'max:200'],
            'parentesco_id' => [
                'sometimes',
                'integer',
                Rule::exists('parentesco', 'id')->where('activo', true),
            ],
            'telefono' => ['sometimes', 'string', 'max:30', new TutorPhoneRule(TutorPhoneRule::ALTERNATE)],
            'version' => ['required', 'integer', 'min:1'],
            'paciente_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parentesco_id.exists' => 'El parentesco seleccionado no existe o está inactivo.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
