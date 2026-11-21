<?php

namespace App\Modules\Pacientes\Http\Requests\Registro;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Enums\Sexo;
use App\Modules\Pacientes\Exceptions\PacienteSensitiveFieldsForbiddenException;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de paciente con tutor principal (04.01/RF-001..RF-004).
 */
final class StorePacienteRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d'],
            'sexo' => ['required', Rule::in(Sexo::values())],
            'ci' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'tipo_diagnostico_id' => ['nullable', 'integer', 'min:1'],
            'sucursal_registro_id' => ['required', 'integer', Rule::exists('sucursal', 'id')],
            'alergias' => ['nullable', 'string', 'max:2000'],
            'condiciones_medicas' => ['nullable', 'string', 'max:2000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],

            'tutor_principal' => ['required', 'array'],
            'tutor_principal.modo' => ['required', Rule::in(['existente', 'nuevo'])],
            'tutor_principal.tutor_id' => ['required_if:tutor_principal.modo,existente', 'integer', Rule::exists('tutor', 'id')],
            'tutor_principal.nombre_completo' => ['required_if:tutor_principal.modo,nuevo', 'nullable', 'string', 'max:200'],
            'tutor_principal.ci' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\-\s]+$/u'],
            'tutor_principal.telefono' => ['required_if:tutor_principal.modo,nuevo', 'nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'tutor_principal.telefono_alternativo' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'tutor_principal.email' => ['nullable', 'email:rfc', 'max:150'],
            'tutor_principal.direccion' => ['nullable', 'string', 'max:500'],
            'tutor_principal.parentesco' => ['required_if:tutor_principal.modo,nuevo', 'nullable', 'string', 'max:50'],

            'confirmar_duplicado' => ['sometimes', 'boolean'],
            'motivo_confirmacion' => ['nullable', 'string', 'max:500', 'required_if:confirmar_duplicado,true'],

            'estado' => ['prohibited'],
            'codigo' => ['prohibited'],
            'version' => ['prohibited'],
            'sucursal_inscripcion_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del paciente es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
            'sexo.required' => 'El sexo es obligatorio.',
            'sexo.in' => 'El sexo debe ser M o F.',
            'ci.regex' => 'El documento solo admite letras, números, espacios y guiones.',
            'sucursal_registro_id.required' => 'La sucursal de registro es obligatoria.',
            'sucursal_registro_id.exists' => 'La sucursal de registro no existe.',
            'tutor_principal.required' => 'Todo paciente activo debe tener un tutor principal.',
            'tutor_principal.modo.required' => 'Indicá si el tutor es existente o nuevo.',
            'tutor_principal.tutor_id.required_if' => 'Seleccioná el tutor existente.',
            'tutor_principal.tutor_id.exists' => 'El tutor seleccionado no existe.',
            'tutor_principal.nombre_completo.required_if' => 'El nombre del tutor es obligatorio.',
            'tutor_principal.telefono.required_if' => 'El teléfono del tutor es obligatorio.',
            'tutor_principal.telefono.regex' => 'El teléfono debe contener solo números, espacios, +, paréntesis o guiones (6 a 30 caracteres).',
            'tutor_principal.parentesco.required_if' => 'El parentesco del tutor es obligatorio.',
            'motivo_confirmacion.required_if' => 'Para confirmar que no es un duplicado tenés que ingresar un motivo.',
        ];
    }
}
