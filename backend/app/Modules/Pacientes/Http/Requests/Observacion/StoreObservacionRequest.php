<?php

namespace App\Modules\Pacientes\Http\Requests\Observacion;

use App\Modules\Pacientes\Exceptions\FichaIngresoSensitiveFieldsForbiddenException;
use App\Modules\Pacientes\Support\FichaIngresoPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de una observación fechada (04.03 Fase C, RF-006).
 */
final class StoreObservacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->can(FichaIngresoPermission::VIEW_SENSITIVE)) {
            throw new FichaIngresoSensitiveFieldsForbiddenException(['texto']);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m-d'],
            'texto' => ['required', 'string', 'max:4000'],
            'observacion_referencia_id' => [
                'nullable',
                'integer',
                Rule::exists('ficha_ingreso_observacion', 'id'),
            ],
            'ficha_ingreso_id' => ['prohibited'],
            'usuario_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'Indicá la fecha de la observación.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'texto.required' => 'Escribí el texto de la observación.',
            'texto.max' => 'La observación no puede superar los 4000 caracteres.',
            'observacion_referencia_id.exists' => 'La observación de referencia no existe.',
        ];
    }
}
