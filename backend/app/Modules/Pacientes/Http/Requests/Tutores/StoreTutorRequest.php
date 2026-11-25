<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use App\Modules\Pacientes\Rules\TutorPhoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de tutor (04.02/RF-001, 04.02/RF-002).
 */
final class StoreTutorRequest extends FormRequest
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
            'ci' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'telefono' => ['required', 'string', 'max:30', new TutorPhoneRule(TutorPhoneRule::PRINCIPAL)],
            'telefono_alternativo' => ['nullable', 'string', 'max:30', new TutorPhoneRule(TutorPhoneRule::ALTERNATE)],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'parentesco_id' => [
                'nullable',
                'integer',
                Rule::exists('parentesco', 'id')->where('activo', true),
            ],
            'parentesco' => ['nullable', 'string', 'max:50'],
            'sucursal_registro_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'activo' => ['prohibited'],
            'version' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('parentesco_id') === null
                    && trim((string) $this->input('parentesco', '')) === '') {
                    $validator->errors()->add(
                        'parentesco_id',
                        'Seleccioná el parentesco del tutor.',
                    );
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
            'nombre_completo.required' => 'El nombre completo del tutor es obligatorio.',
            'telefono.required' => 'El teléfono principal es obligatorio.',
            'parentesco_id.exists' => 'El parentesco seleccionado no existe o está inactivo.',
            'sucursal_registro_id.exists' => 'La sucursal de registro no existe.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
        ];
    }
}
