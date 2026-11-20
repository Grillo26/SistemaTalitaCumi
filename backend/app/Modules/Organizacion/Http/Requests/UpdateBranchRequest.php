<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Support\BranchPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edición de sucursal.
 *
 * RF-006, RF-002, RF-003 y RF-013 / RN-001.
 *
 * La autorización por campo distingue:
 * - edición estructural (código/nombre): sucursal.editar;
 * - edición de contacto/dirección: sucursal.editar_contacto o sucursal.editar.
 * El estado no se modifica por esta vía: se usan Activar/Desactivar.
 */
final class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $changesStructuralFields = $this->hasAny(['codigo', 'nombre']);
        $changesContactFields = $this->hasAny(['direccion', 'telefono', 'email']);

        if ($changesStructuralFields && ! $user->can(BranchPermission::EDIT)) {
            return false;
        }

        if ($changesContactFields
            && ! $user->can(BranchPermission::EDIT_CONTACT)
            && ! $user->can(BranchPermission::EDIT)) {
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
            'codigo' => ['sometimes', 'required', 'string', 'max:50'],
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
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
                foreach (['codigo', 'nombre', 'direccion', 'telefono', 'email'] as $field) {
                    if ($this->exists($field)) {
                        return;
                    }
                }

                $validator->errors()->add('codigo', 'Enviá al menos un campo para actualizar.');
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código no puede quedar vacío.',
            'nombre.required' => 'El nombre no puede quedar vacío.',
            'telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'email.email' => 'El correo no tiene un formato válido.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
        ];
    }
}
