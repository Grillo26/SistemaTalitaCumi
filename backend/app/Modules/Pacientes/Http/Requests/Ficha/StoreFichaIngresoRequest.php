<?php

namespace App\Modules\Pacientes\Http\Requests\Ficha;

use App\Modules\Pacientes\Exceptions\FichaIngresoSensitiveFieldsForbiddenException;
use App\Modules\Pacientes\Support\FichaIngresoFields;
use App\Modules\Pacientes\Support\FichaIngresoPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de ficha de ingreso (04.03/RF-001, 04.03/RF-002).
 */
final class StoreFichaIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sensitive = array_values(array_filter(
            FichaIngresoFields::SENSITIVE_EDITABLE,
            fn (string $field): bool => $this->filled($field),
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
            'motivo_consulta_inicial' => ['required', 'string', 'max:2000'],
            'fecha_ingreso' => ['required', 'date_format:Y-m-d'],
            'sucursal_ingreso_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'composicion_familiar' => ['nullable', 'string', 'max:2000'],
            'antecedentes_familiares' => ['nullable', 'string', 'max:4000'],
            'observaciones' => ['nullable', 'string', 'max:4000'],
            'derivado_por' => ['nullable', 'string', 'max:200'],
            'derivado_por_institucion' => ['nullable', 'string', 'max:150'],
            'derivado_por_contacto' => ['nullable', 'string', 'max:150'],
            'estado' => ['prohibited'],
            'version' => ['prohibited'],
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
            'motivo_consulta_inicial.required' => 'El motivo de consulta es obligatorio.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date_format' => 'La fecha de ingreso debe tener el formato AAAA-MM-DD.',
            'sucursal_ingreso_id.exists' => 'La sucursal de ingreso no existe.',
        ];
    }
}
