<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Agenda Semanal] RF-001, RF-002, RF-005, RF-011, RNF-002.
 *
 * La autorización real es el middleware de permiso; este Request valida
 * formato y coherencia de los filtros.
 */
final class IndexAgendaSemanalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['sala_sin_asignar', 'profesional_sin_asignar'] as $key) {
            if ($this->has($key)) {
                $this->merge([$key => $this->boolean($key)]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sucursal_id' => ['nullable', 'integer', 'min:1', 'exists:sucursal,id'],
            'semana' => ['nullable', 'date_format:Y-m-d'],
            'area_id' => ['nullable', 'integer', 'min:1', 'exists:area,id'],
            'sala_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:sala,id',
                Rule::prohibitedIf(fn (): bool => $this->boolean('sala_sin_asignar')),
            ],
            'sala_sin_asignar' => ['nullable', 'boolean'],
            'profesional_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:profesional,id',
                Rule::prohibitedIf(fn (): bool => $this->boolean('profesional_sin_asignar')),
            ],
            'profesional_sin_asignar' => ['nullable', 'boolean'],
            'servicio_id' => ['nullable', 'integer', 'min:1', 'exists:servicio,id'],
            'estado' => ['nullable', Rule::enum(EstadoTurno::class)],
        ];
    }
}
