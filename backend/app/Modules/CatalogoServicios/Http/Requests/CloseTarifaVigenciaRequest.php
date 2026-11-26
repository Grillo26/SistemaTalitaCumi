<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cierre de vigencia de tarifa (RF-T12 / RN-T12).
 */
final class CloseTarifaVigenciaRequest extends FormRequest
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
            'vigente_hasta' => ['required', 'date_format:Y-m-d'],
            'motivo' => ['required', 'string', 'max:1000'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vigente_hasta.required' => 'La fecha de fin de vigencia es obligatoria.',
            'vigente_hasta.date_format' => 'La fecha de fin debe tener formato AAAA-MM-DD.',
            'motivo.required' => 'El motivo del cierre es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
            'version.required' => 'La versión del registro es obligatoria.',
        ];
    }
}
