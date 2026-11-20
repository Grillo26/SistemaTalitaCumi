<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Support\SalasAmbientesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edición de sala grupal (RF-010 / RF-017).
 *
 * Autorización por campo:
 * - estructura (sucursal, área, tipo, turno): sala.editar_estructura;
 * - datos operativos (nombre, observaciones): sala.editar_datos o estructura.
 */
final class UpdateSalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $structural = $this->hasAny(['sucursal_id', 'area_id', 'tipo_sala_id', 'turno_catalogo_id']);
        $data = $this->hasAny(['nombre', 'observaciones']);

        if ($structural && ! $user->can(SalasAmbientesPermission::SALA_EDIT_STRUCTURE)) {
            return false;
        }

        if ($data
            && ! $user->can(SalasAmbientesPermission::SALA_EDIT_DATA)
            && ! $user->can(SalasAmbientesPermission::SALA_EDIT_STRUCTURE)) {
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
            'area_id' => ['sometimes', 'required', 'integer', 'exists:area,id'],
            'tipo_sala_id' => ['sometimes', 'required', 'integer', 'exists:tipo_sala,id'],
            'turno_catalogo_id' => ['sometimes', 'required', 'integer', 'exists:turno_catalogo,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
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
                foreach (['sucursal_id', 'area_id', 'tipo_sala_id', 'turno_catalogo_id', 'nombre', 'observaciones'] as $field) {
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
            'area_id.required' => 'El área no puede quedar vacía.',
            'tipo_sala_id.required' => 'El tipo de sala no puede quedar vacío.',
            'turno_catalogo_id.required' => 'El turno no puede quedar vacío.',
            'nombre.required' => 'El nombre no puede quedar vacío.',
            'activo.prohibited' => 'El estado se cambia con las acciones Activar o Desactivar.',
        ];
    }
}
