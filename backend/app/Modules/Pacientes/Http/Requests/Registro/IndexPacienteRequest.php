<?php

namespace App\Modules\Pacientes\Http\Requests\Registro;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del listado de pacientes (04.01/RF-005).
 */
final class IndexPacienteRequest extends FormRequest
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
            'estado' => ['nullable', Rule::in(EstadoPaciente::values())],
            'tipo_diagnostico_id' => ['nullable', 'integer', 'min:1'],
            'sin_diagnostico' => ['nullable', 'boolean'],
            'sucursal_registro_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'sucursal_inscripcion_id' => ['prohibited'],
            'tipo_servicio_id' => ['prohibited'],
            'estado_pago' => ['prohibited'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:apellido,nombre_completo,codigo,fecha_nacimiento,estado,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.min' => 'Ingresá al menos 2 caracteres para buscar.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'sucursal_inscripcion_id.prohibited' => 'El filtro por sucursal de inscripción estará disponible con el módulo 06.',
            'tipo_servicio_id.prohibited' => 'El filtro por tipo de servicio estará disponible con Inscripciones (módulo 06).',
            'estado_pago.prohibited' => 'El filtro por estado de pago estará disponible con Pagos y caja (módulo 08).',
            'per_page.max' => 'El máximo de registros por página es 50.',
        ];
    }
}
