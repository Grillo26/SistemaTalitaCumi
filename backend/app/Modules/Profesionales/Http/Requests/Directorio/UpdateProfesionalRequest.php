<?php

namespace App\Modules\Profesionales\Http\Requests\Directorio;

use App\Modules\Profesionales\Support\ProfesionalesModule;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edición de profesional (03/RF-D06, 03/RF-D09 / 03/RF-D14).
 *
 * La autorización distingue datos estructurales de contacto. Administración
 * solo puede enviar teléfonos, correo y versión.
 */
final class UpdateProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $changesData = $this->exists('nombre_completo')
            || $this->exists('ci')
            || $this->exists('matricula')
            || $this->exists('especialidad_principal_id')
            || $this->exists('especialidades_secundarias');

        $changesContact = $this->exists('telefono')
            || $this->exists('telefono_2')
            || $this->exists('email');

        if ($changesData && ! $user->can(ProfesionalesPermission::PROFESSIONAL_EDIT_DATA)) {
            return false;
        }

        if ($changesContact
            && ! $user->can(ProfesionalesPermission::PROFESSIONAL_EDIT_CONTACT)
            && ! $user->can(ProfesionalesPermission::PROFESSIONAL_EDIT_DATA)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['sometimes', 'required', 'string', 'max:200'],
            'ci' => ['sometimes', 'required', 'string', 'max:30', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'telefono_2' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
            'matricula' => ['sometimes', 'nullable', 'string', 'max:50'],
            'especialidad_principal_id' => [
                'sometimes',
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
                $fields = [
                    'nombre_completo',
                    'ci',
                    'telefono',
                    'telefono_2',
                    'email',
                    'matricula',
                    'especialidad_principal_id',
                    'especialidades_secundarias',
                ];

                $hasChanges = false;

                foreach ($fields as $field) {
                    if ($this->exists($field)) {
                        $hasChanges = true;
                        break;
                    }
                }

                if (! $hasChanges) {
                    $validator->errors()->add('nombre_completo', 'Enviá al menos un campo para actualizar.');
                }

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
            'nombre_completo.required' => 'El nombre completo no puede quedar vacío.',
            'ci.required' => 'El documento de identidad no puede quedar vacío.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
            'telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'telefono_2.regex' => 'El segundo teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'email.email' => 'El correo no tiene un formato válido.',
            'especialidad_principal_id.required' => 'Seleccioná una especialidad principal.',
            'especialidad_principal_id.exists' => 'La especialidad principal no existe o está inactiva.',
            'especialidades_secundarias.*.exists' => 'Una de las especialidades secundarias no existe o está inactiva.',
            'especialidades_secundarias.*.distinct' => 'No se puede repetir una especialidad secundaria.',
            'version.required' => 'La versión del registro es obligatoria.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
        ];
    }
}
