<?php

namespace App\Modules\Pacientes\Http\Requests\Registro;

use App\Modules\Pacientes\Enums\Sexo;
use App\Modules\Pacientes\Exceptions\PacienteSensitiveFieldsForbiddenException;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de paciente (04.01/RF-007).
 */
final class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sensitive = array_values(array_filter(
            ['alergias', 'condiciones_medicas'],
            fn (string $field): bool => $this->filled($field),
        ));

        if ($sensitive !== [] && ! $this->user()?->can(PacientesPermission::PATIENT_VIEW_SENSITIVE)) {
            throw new PacienteSensitiveFieldsForbiddenException($sensitive);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100'],
            'apellido_paterno' => ['sometimes', 'string', 'max:100'],
            'apellido_materno' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['sometimes', 'date_format:Y-m-d'],
            'sexo' => ['sometimes', Rule::in(Sexo::values())],
            'ci' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'tipo_diagnostico_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'alergias' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'condiciones_medicas' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'version' => ['required', 'integer', 'min:1'],

            'estado' => ['prohibited'],
            'codigo' => ['prohibited'],
            'sucursal_registro_id' => ['prohibited'],
            'tutor_principal' => ['prohibited'],
            'confirmar_duplicado' => ['prohibited'],
            'motivo_confirmacion' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
            'sexo.in' => 'El sexo debe ser M o F.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
            'version.required' => 'La versión del registro es obligatoria.',
            'sucursal_registro_id.prohibited' => 'La sucursal de registro no se puede cambiar desde la edición.',
            'estado.prohibited' => 'El estado se cambia desde la acción de cambio de estado.',
        ];
    }
}
