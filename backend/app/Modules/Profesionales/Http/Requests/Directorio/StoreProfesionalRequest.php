<?php

namespace App\Modules\Profesionales\Http\Requests\Directorio;

use App\Modules\Profesionales\Support\ProfesionalesModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta de profesional (03/RF-D01, 03/RF-D03, 03/RF-D09 / 03/RN-01, 03/RN-02).
 */
final class StoreProfesionalRequest extends FormRequest
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
            'ci' => ['required', 'string', 'max:30', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'telefono_2' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'matricula' => ['nullable', 'string', 'max:50'],
            'activo' => ['sometimes', 'boolean'],
            'especialidad_principal_id' => [
                'required',
                'integer',
                Rule::exists('especialidad', 'id')->where('activo', true),
            ],
            'especialidades_secundarias' => ['sometimes', 'array'],
            'especialidades_secundarias.*' => [
                'integer',
                'distinct',
                Rule::exists('especialidad', 'id')->where('activo', true),
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $principal = $this->input('especialidad_principal_id');
                $secondary = (array) $this->input('especialidades_secundarias', []);

                if ($principal !== null && in_array((int) $principal, array_map('intval', $secondary), true)) {
                    $validator->errors()->add(
                        'especialidades_secundarias',
                        'La especialidad principal no puede repetirse como secundaria.',
                    );
                }

                if (! ProfesionalesModule::SECUNDARIAS_HABILITADAS && $secondary !== []) {
                    $validator->errors()->add(
                        'especialidades_secundarias',
                        'Las especialidades secundarias están pendientes de confirmación con el cliente.',
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
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'ci.required' => 'El documento de identidad es obligatorio.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
            'telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'telefono_2.regex' => 'El segundo teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'email.email' => 'El correo no tiene un formato válido.',
            'especialidad_principal_id.required' => 'Seleccioná una especialidad principal.',
            'especialidad_principal_id.exists' => 'La especialidad principal no existe o está inactiva.',
            'especialidades_secundarias.*.exists' => 'Una de las especialidades secundarias no existe o está inactiva.',
            'especialidades_secundarias.*.distinct' => 'No se puede repetir una especialidad secundaria.',
        ];
    }
}
