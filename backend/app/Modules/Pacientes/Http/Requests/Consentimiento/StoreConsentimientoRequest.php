<?php

namespace App\Modules\Pacientes\Http\Requests\Consentimiento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Registro de un consentimiento otorgado o negado (04.03 Fase B).
 */
final class StoreConsentimientoRequest extends FormRequest
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
            'consentimiento_tipo_id' => ['required', 'integer', Rule::exists('consentimiento_tipo', 'id')],
            'tutor_id' => ['required', 'integer', Rule::exists('tutor', 'id')],
            'resultado' => ['required', Rule::in(['otorgado', 'negado'])],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'ficha_ingreso_id' => ['prohibited'],
            'revoca_a_id' => ['prohibited'],
            'usuario_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consentimiento_tipo_id.required' => 'Seleccioná un tipo de consentimiento.',
            'consentimiento_tipo_id.exists' => 'El tipo de consentimiento no existe.',
            'tutor_id.required' => 'Seleccioná el tutor que autoriza.',
            'tutor_id.exists' => 'El tutor no existe.',
            'resultado.required' => 'Indicá el resultado del consentimiento.',
            'resultado.in' => 'El resultado debe ser otorgado o negado.',
            'fecha.required' => 'Indicá la fecha del consentimiento.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'motivo.max' => 'La observación no puede superar los 1000 caracteres.',
        ];
    }
}
