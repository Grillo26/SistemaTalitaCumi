<?php

namespace App\Modules\Pacientes\Http\Requests\Tutores;

use App\Modules\Pacientes\Exceptions\TutorContactFieldsForbiddenException;
use App\Modules\Pacientes\Exceptions\TutorIdentityFieldsForbiddenException;
use App\Modules\Pacientes\Rules\TutorPhoneRule;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de tutor (04.02/RF-009).
 */
final class UpdateTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $identity = ['nombre_completo', 'ci', 'parentesco_id', 'parentesco'];
        $contact = ['telefono', 'telefono_alternativo', 'email', 'direccion'];

        $identityPresent = array_values(array_intersect($identity, array_keys($this->all())));
        $contactPresent = array_values(array_intersect($contact, array_keys($this->all())));

        if ($identityPresent !== [] && ! $this->user()?->can(PacientesPermission::TUTOR_EDIT)) {
            throw new TutorIdentityFieldsForbiddenException($identityPresent);
        }

        if ($contactPresent !== []
            && ! $this->user()?->can(PacientesPermission::TUTOR_EDIT)
            && ! $this->user()?->can(PacientesPermission::TUTOR_EDIT_CONTACT)) {
            throw new TutorContactFieldsForbiddenException($contactPresent);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['sometimes', 'string', 'max:200'],
            'ci' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'telefono' => ['sometimes', 'string', 'max:30', new TutorPhoneRule(TutorPhoneRule::PRINCIPAL)],
            'telefono_alternativo' => ['sometimes', 'nullable', 'string', 'max:30', new TutorPhoneRule(TutorPhoneRule::ALTERNATE)],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'parentesco_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('parentesco', 'id')->where('activo', true),
            ],
            'parentesco' => ['sometimes', 'nullable', 'string', 'max:50'],
            'version' => ['required', 'integer', 'min:1'],
            'activo' => ['prohibited'],
            'sucursal_registro_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parentesco_id.exists' => 'El parentesco seleccionado no existe o está inactivo.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
            'version.required' => 'La versión del registro es obligatoria.',
            'sucursal_registro_id.prohibited' => 'La sucursal de registro del tutor no se cambia desde la edición.',
        ];
    }
}
