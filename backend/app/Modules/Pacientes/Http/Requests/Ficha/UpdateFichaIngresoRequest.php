<?php

namespace App\Modules\Pacientes\Http\Requests\Ficha;

use App\Modules\Pacientes\Exceptions\FichaIngresoSensitiveFieldsForbiddenException;
use App\Modules\Pacientes\Support\FichaIngresoFields;
use App\Modules\Pacientes\Support\FichaIngresoPermission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de ficha de ingreso (04.03/RF-005).
 */
final class UpdateFichaIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sensitive = array_values(array_filter(
            FichaIngresoFields::SENSITIVE_EDITABLE,
            fn (string $field): bool => array_key_exists($field, $this->all()),
        ));

        if ($sensitive !== [] && ! $this->user()?->can(FichaIngresoPermission::VIEW_SENSITIVE)) {
            throw new FichaIngresoSensitiveFieldsForbiddenException($sensitive);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo_consulta_inicial' => ['sometimes', 'required', 'string', 'max:2000'],
            'fecha_ingreso' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'composicion_familiar' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'antecedentes_familiares' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'derivado_por' => ['sometimes', 'nullable', 'string', 'max:200'],
            'derivado_por_institucion' => ['sometimes', 'nullable', 'string', 'max:150'],
            'derivado_por_contacto' => ['sometimes', 'nullable', 'string', 'max:150'],
            'version' => ['required', 'integer', 'min:1'],
            'estado' => ['prohibited'],
            'sucursal_ingreso_id' => ['prohibited'],
            'paciente_id' => ['prohibited'],
            'tutor_principal_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_ingreso.date_format' => 'La fecha de ingreso debe tener el formato AAAA-MM-DD.',
            'version.required' => 'La versión del registro es obligatoria.',
            'sucursal_ingreso_id.prohibited' => 'La sucursal de ingreso no se cambia desde la edición.',
        ];
    }
}
