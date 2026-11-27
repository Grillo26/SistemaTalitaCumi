<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-011 / CA-011.
 */
final class IndexEstadoPacienteRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'min:2', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoPaciente::class)],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:apellido,nombre_completo,codigo,fecha_nacimiento,estado,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
