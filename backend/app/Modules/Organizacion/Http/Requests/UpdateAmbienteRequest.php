<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Enums\UsoAmbiente;
use App\Modules\Organizacion\Support\SalasAmbientesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Edición de ambiente (RF-010 / RF-017).
 *
 * Autorización por campo:
 * - estructura (sucursal, uso): ambiente.editar_estructura;
 * - datos operativos (nombre, observaciones): ambiente.editar_datos o estructura.
 */
final class UpdateAmbienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $structural = $this->hasAny(['sucursal_id', 'uso']);
        $data = $this->hasAny(['nombre', 'observaciones']);

        if ($structural && ! $user->can(SalasAmbientesPermission::AMBIENTE_EDIT_STRUCTURE)) {
            return false;
        }

        if ($data
            && ! $user->can(SalasAmbientesPermission::AMBIENTE_EDIT_DATA)
            && ! $user->can(SalasAmbientesPermission::AMBIENTE_EDIT_STRUCTURE)) {
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
            'sucursal_id' => ['sometimes', 'required', 'integer', 'exists:sucursal,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'uso' => ['sometimes', 'required', Rule::enum(UsoAmbiente::class)],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:2000'],
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
                foreach (['sucursal_id', 'nombre', 'uso', 'observaciones'] as $field) {
                    if ($this->exists($field)) {
                        return;
                    }
                }

                $validator->errors()->add('nombre', 'Enviá al menos un campo para actualizar.');
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sucursal_id.required' => 'La sucursal no puede quedar vacía.',
            'nombre.required' => 'El nombre no puede quedar vacío.',
            'uso.required' => 'El uso previsto no puede quedar vacío.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
        ];
    }
}
