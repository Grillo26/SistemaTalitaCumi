<?php

namespace App\Modules\Pacientes\Http\Requests\Contactos;

use App\Modules\Pacientes\Rules\TutorPhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de contacto de emergencia (04.02/RF-012).
 */
final class StoreContactoEmergenciaRequest extends FormRequest
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
            'nombre_completo' => ['required', 'string', 'max:200'],
            'parentesco_id' => [
                'required',
                'integer',
                Rule::exists('parentesco', 'id')->where('activo', true),
            ],
            'telefono' => ['required', 'string', 'max:30', new TutorPhoneRule(TutorPhoneRule::ALTERNATE)],
            'version' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_completo.required' => 'El nombre del contacto es obligatorio.',
            'parentesco_id.required' => 'Seleccioná el parentesco del contacto.',
            'parentesco_id.exists' => 'El parentesco seleccionado no existe o está inactivo.',
            'telefono.required' => 'El teléfono del contacto es obligatorio.',
        ];
    }
}
